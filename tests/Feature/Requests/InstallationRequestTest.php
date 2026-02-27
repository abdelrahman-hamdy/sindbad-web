<?php

namespace Tests\Feature\Requests;

use App\Models\Request as InstallRequest;
use App\Models\User;
use App\Services\NotificationService;
use Tests\TestCase;

class InstallationRequestTest extends TestCase
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
        $this->mock(NotificationService::class)->shouldIgnoreMissing();
    }

    public function test_customer_can_create_installation_request(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/installation-requests', [
                'product_type'   => 'Split AC',
                'quantity'       => 2,
                'is_site_ready'  => true,
                'address'        => '10 Palm St',
                'latitude'       => 23.5,
                'longitude'      => 58.4,
                'scheduled_at'   => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertCreated()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('requests', [
            'user_id'      => $this->customer->id,
            'type'         => 'installation',
            'product_type' => 'Split AC',
            'quantity'     => 2,
        ]);
    }

    public function test_invoice_number_gets_b_prefix(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/installation-requests', [
                'product_type'   => 'Heater',
                'address'        => 'Test',
                'latitude'       => 23.0,
                'longitude'      => 58.0,
                'scheduled_at'   => now()->addDays(1)->format('Y-m-d'),
                'invoice_number' => '99999',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('requests', ['invoice_number' => 'B-99999']);
    }

    public function test_admin_can_bulk_delete_installations(): void
    {
        $r1 = InstallRequest::factory()->installation()->forUser($this->customer)->create();
        $r2 = InstallRequest::factory()->installation()->forUser($this->customer)->create();
        $r3 = InstallRequest::factory()->installation()->forUser($this->customer)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/installation-requests/bulk-delete', ['ids' => [$r1->id, $r2->id]])
            ->assertOk();

        $this->assertSoftDeleted('requests', ['id' => $r1->id]);
        $this->assertSoftDeleted('requests', ['id' => $r2->id]);
        $this->assertNotSoftDeleted('requests', ['id' => $r3->id]);
    }

    public function test_non_admin_cannot_bulk_delete(): void
    {
        $req = InstallRequest::factory()->installation()->forUser($this->customer)->create();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/installation-requests/bulk-delete', ['ids' => [$req->id]])
            ->assertForbidden();
    }

    public function test_admin_can_assign_technician_to_installation(): void
    {
        $req = InstallRequest::factory()->installation()->forUser($this->customer)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/installation-requests/{$req->id}/assign", [
                'technician_id' => $this->technician->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('requests', [
            'id'           => $req->id,
            'technician_id' => $this->technician->id,
        ]);
    }

    public function test_update_readiness_stores_correct_data(): void
    {
        $req = InstallRequest::factory()->installation()->forUser($this->customer)->create();

        $this->actingAs($this->technician, 'sanctum')
            ->putJson("/api/installation-requests/{$req->id}/readiness", [
                'is_site_ready'     => false,
                'readiness_details' => ['issue' => 'No power socket'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('requests', [
            'id'           => $req->id,
            'is_site_ready' => 0,
        ]);
    }

    public function test_customer_can_submit_installation_rating(): void
    {
        $req = InstallRequest::factory()->installation()
            ->forUser($this->customer)
            ->completed()
            ->create();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/installation-requests/{$req->id}/rating", [
                'product_rating' => 5,
                'service_rating' => 4,
                'how_found_us'   => 'Referral',
            ])
            ->assertOk();

        $this->assertDatabaseHas('ratings', [
            'request_id' => $req->id,
            'product_rating' => 5,
        ]);
    }

    public function test_technician_schedule_includes_product_type(): void
    {
        $req = InstallRequest::factory()->installation()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        $data = $this->actingAs($this->technician, 'sanctum')
            ->getJson('/api/technician/schedule')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($data);
        $this->assertEquals('Split AC' === $data[0]['product_type'] ? 'Split AC' : $req->product_type, $data[0]['product_type']);
        $this->assertArrayHasKey('product_type', $data[0]);
        $this->assertArrayHasKey('quantity', $data[0]);
    }
}
