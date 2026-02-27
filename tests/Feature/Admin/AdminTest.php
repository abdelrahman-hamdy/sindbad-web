<?php

namespace Tests\Feature\Admin;

use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Odoo\OdooServiceInterface;
use Tests\TestCase;

class AdminTest extends TestCase
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
        $this->mock(OdooServiceInterface::class)->shouldIgnoreMissing();
    }

    // ── Middleware guard ─────────────────────────────────────────────────────

    public function test_non_admin_cannot_access_admin_stats(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/admin/stats')
            ->assertForbidden();
    }

    public function test_technician_cannot_access_admin_users(): void
    {
        $this->actingAs($this->technician, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_admin(): void
    {
        $this->getJson('/api/admin/stats')->assertStatus(401);
    }

    // ── Dashboard Stats ──────────────────────────────────────────────────────

    public function test_dashboard_stats_returns_correct_counts(): void
    {
        ServiceRequest::factory()->forUser($this->customer)->create(['type' => 'service', 'status' => 'pending']);
        ServiceRequest::factory()->forUser($this->customer)->create(['type' => 'service', 'status' => 'completed', 'completed_at' => now()]);
        ServiceRequest::factory()->installation()->forUser($this->customer)->create(['status' => 'pending']);

        $data = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/stats')
            ->assertOk()
            ->json('data');

        $this->assertEquals(2, $data['service']['total']);
        $this->assertEquals(1, $data['service']['pending']);
        $this->assertEquals(1, $data['service']['completed']);
        $this->assertEquals(1, $data['installation']['total']);
        $this->assertEquals(1, $data['customers']);
        $this->assertEquals(1, $data['technicians']);
    }

    // ── User Management ──────────────────────────────────────────────────────

    public function test_admin_can_list_users(): void
    {
        User::factory()->customer()->count(3)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users', [
                'name'     => 'New Customer',
                'phone'    => '96899000001',
                'password' => 'password123',
                'role'     => 'customer',
            ])
            ->assertCreated()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('users', ['phone' => '96899000001']);
    }

    public function test_admin_can_create_customer_with_manual_orders(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users', [
                'name'     => 'Customer With Orders',
                'phone'    => '96899000002',
                'password' => 'password123',
                'role'     => 'customer',
                'orders'   => [
                    [
                        'invoice_number'  => 'INV-001',
                        'total_amount'    => 500.0,
                        'paid_amount'     => 250.0,
                        'remaining_amount'=> 250.0,
                        'status'          => 'partial',
                    ],
                ],
            ])
            ->assertCreated();

        $user = User::where('phone', '96899000002')->first();
        $this->assertNotNull($user);
        $this->assertCount(1, $user->manualOrders);
        $this->assertEquals('INV-001', $user->manualOrders->first()->invoice_number);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/users/{$user->id}", [
                'name'      => 'Updated Name',
                'is_active' => false,
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name', 'is_active' => 0]);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$this->admin->id}")
            ->assertStatus(422);
    }

    public function test_admin_can_bulk_delete_users(): void
    {
        $u1 = User::factory()->customer()->create();
        $u2 = User::factory()->customer()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-delete', ['ids' => [$u1->id, $u2->id]])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $u1->id]);
        $this->assertDatabaseMissing('users', ['id' => $u2->id]);
    }

    // ── Lookup ───────────────────────────────────────────────────────────────

    public function test_lookup_returns_404_for_unknown_phone(): void
    {
        $odoo = $this->mock(OdooServiceInterface::class);
        $odoo->shouldReceive('findCustomerByPhoneOrName')->andReturn(null);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/users/lookup/96800000000')
            ->assertStatus(404);
    }

    public function test_lookup_returns_user_when_found_locally(): void
    {
        $odoo = $this->mock(OdooServiceInterface::class);
        $odoo->shouldReceive('findCustomerByPhoneOrName')->andReturn(null);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/users/lookup/{$this->customer->phone}")
            ->assertOk()
            ->assertJsonPath('data.user.phone', $this->customer->phone);
    }

    // ── Get User Details ─────────────────────────────────────────────────────

    public function test_get_user_details_returns_full_profile(): void
    {
        ServiceRequest::factory()->forUser($this->customer)->count(2)->create();

        $data = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/users/{$this->customer->id}")
            ->assertOk()
            ->json('data');

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('requests', $data);
        $this->assertArrayHasKey('assigned_requests', $data);
        $this->assertArrayHasKey('manual_orders', $data);
        $this->assertCount(2, $data['requests']);
    }

    // ── Reports ──────────────────────────────────────────────────────────────

    public function test_admin_can_get_performance_reports(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/performance')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_admin_can_get_ratings_report(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/ratings')
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }
}
