<?php

namespace Tests\Feature\Requests;

use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Services\NotificationService;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    private User $admin;
    private User $customer;
    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin      = User::factory()->admin()->create();
        $this->customer   = User::factory()->customer()->create();
        $this->technician = User::factory()->technician()->create();

        // Silence all notifications by default
        $this->mock(NotificationService::class)->shouldIgnoreMissing();
    }

    // ── Create ──────────────────────────────────────────────────────────────

    public function test_customer_can_create_service_request(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/service-requests', [
                'service_type' => 'maintenance',
                'description'  => 'AC not working',
                'address'      => '123 Test St',
                'latitude'     => 23.5880,
                'longitude'    => 58.3829,
                'scheduled_at' => now()->addDays(2)->format('Y-m-d'),
            ])
            ->assertCreated()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('requests', [
            'user_id' => $this->customer->id,
            'type'    => 'service',
            'status'  => 'pending',
        ]);
    }

    public function test_admin_notification_sent_on_request_creation(): void
    {
        $notification = $this->mock(NotificationService::class);
        $notification->shouldReceive('notifyAdmins')->once();
        $notification->shouldIgnoreMissing();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/service-requests', [
                'service_type' => 'repair',
                'address'      => '456 Main Rd',
                'latitude'     => 23.5880,
                'longitude'    => 58.3829,
                'scheduled_at' => now()->addDays(1)->format('Y-m-d'),
            ])
            ->assertCreated();
    }

    public function test_invoice_number_gets_t_prefix(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/service-requests', [
                'service_type'   => 'inspection',
                'address'        => 'Test',
                'latitude'       => 23.0,
                'longitude'      => 58.0,
                'scheduled_at'   => now()->addDays(1)->format('Y-m-d'),
                'invoice_number' => '12345',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('requests', ['invoice_number' => 'T-12345']);
    }

    // ── Read ─────────────────────────────────────────────────────────────────

    public function test_customer_can_only_see_own_requests(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        ServiceRequest::factory()->forUser($this->customer)->create();
        ServiceRequest::factory()->forUser($otherCustomer)->create();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/service-requests')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_see_all_service_requests(): void
    {
        ServiceRequest::factory()->forUser($this->customer)->create();
        ServiceRequest::factory()->forUser(User::factory()->customer()->create())->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/service-requests')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    public function test_customer_can_view_own_request(): void
    {
        $req = ServiceRequest::factory()->forUser($this->customer)->create();

        $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/service-requests/{$req->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $req->id);
    }

    public function test_customer_cannot_view_others_request(): void
    {
        $other = User::factory()->customer()->create();
        $req   = ServiceRequest::factory()->forUser($other)->create();

        $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/service-requests/{$req->id}")
            ->assertForbidden();
    }

    // ── Assign ───────────────────────────────────────────────────────────────

    public function test_assigning_technician_sends_notifications(): void
    {
        $req = ServiceRequest::factory()->forUser($this->customer)->create();

        $notification = $this->mock(NotificationService::class);
        $notification->shouldReceive('notifyUser')->twice(); // technician + customer
        $notification->shouldIgnoreMissing();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/service-requests/{$req->id}/assign", [
                'technician_id' => $this->technician->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('requests', [
            'id'            => $req->id,
            'technician_id' => $this->technician->id,
            'status'        => 'assigned',
        ]);
    }

    // ── Status Updates ───────────────────────────────────────────────────────

    public function test_status_update_to_completed_sets_completed_at(): void
    {
        $req = ServiceRequest::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        // Give a rating first so technician can complete
        $req->rating()->create([
            'user_id'        => $this->customer->id,
            'service_rating' => 5,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/service-requests/{$req->id}/status", ['status' => 'completed'])
            ->assertOk();

        $this->assertNotNull(ServiceRequest::find($req->id)->completed_at);
    }

    public function test_status_update_sends_notification_to_customer(): void
    {
        $req = ServiceRequest::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        $notification = $this->mock(NotificationService::class);
        $notification->shouldReceive('notifyUser')->atLeast()->once();
        $notification->shouldIgnoreMissing();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/service-requests/{$req->id}/status", ['status' => 'in_progress'])
            ->assertOk();
    }

    public function test_technician_cannot_complete_request_without_rating(): void
    {
        $req = ServiceRequest::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        $this->actingAs($this->technician, 'sanctum')
            ->postJson("/api/service-requests/{$req->id}/status", ['status' => 'completed'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('requests', [
            'id'     => $req->id,
            'status' => 'completed',
        ]);
    }

    public function test_technician_can_complete_after_rating_submitted(): void
    {
        $req = ServiceRequest::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        $req->rating()->create([
            'user_id'        => $this->customer->id,
            'service_rating' => 4,
        ]);

        $this->actingAs($this->technician, 'sanctum')
            ->postJson("/api/service-requests/{$req->id}/status", ['status' => 'completed'])
            ->assertOk();
    }

    // ── Rating ───────────────────────────────────────────────────────────────

    public function test_customer_can_submit_rating(): void
    {
        $req = ServiceRequest::factory()
            ->forUser($this->customer)
            ->completed()
            ->create();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/service-requests/{$req->id}/rating", [
                'product_rating' => 4,
                'service_rating' => 5,
                'customer_notes' => 'Great service!',
            ])
            ->assertOk();

        $this->assertDatabaseHas('ratings', ['request_id' => $req->id]);
    }

    public function test_cannot_submit_rating_twice(): void
    {
        $req = ServiceRequest::factory()->forUser($this->customer)->completed()->create();
        $req->rating()->create(['user_id' => $this->customer->id, 'service_rating' => 3]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/service-requests/{$req->id}/rating", ['service_rating' => 5])
            ->assertStatus(422);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_admin_can_bulk_delete_service_requests(): void
    {
        $r1 = ServiceRequest::factory()->forUser($this->customer)->create();
        $r2 = ServiceRequest::factory()->forUser($this->customer)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/service-requests/bulk-delete', ['ids' => [$r1->id, $r2->id]])
            ->assertOk();

        $this->assertSoftDeleted('requests', ['id' => $r1->id]);
        $this->assertSoftDeleted('requests', ['id' => $r2->id]);
    }
}
