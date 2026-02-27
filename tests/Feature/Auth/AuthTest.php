<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Odoo\OdooServiceInterface;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_admin_can_login_with_phone_and_password(): void
    {
        $admin = User::factory()->admin()->create([
            'phone'    => '96891234567',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone'    => '96891234567',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'token', 'type', 'user'])
            ->assertJsonFragment(['success' => true]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->customer()->inactive()->create([
            'phone'    => '96899111111',
            'password' => bcrypt('password'),
        ]);

        $this->postJson('/api/auth/login', [
            'phone'    => '96899111111',
            'password' => 'password',
        ])->assertStatus(403);
    }

    public function test_wrong_password_returns_401(): void
    {
        User::factory()->customer()->create([
            'phone'    => '96899222222',
            'password' => bcrypt('correct'),
        ]);

        $this->postJson('/api/auth/login', [
            'phone'    => '96899222222',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_admin_profile_does_not_include_odoo_financials(): void
    {
        $admin = User::factory()->admin()->create();

        // OdooService should NOT be called for admins
        $this->mock(OdooServiceInterface::class)
            ->shouldNotReceive('getCustomerOrders');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonFragment(['success' => true])
            ->assertJsonPath('data.financials', null);
    }

    public function test_technician_profile_does_not_include_odoo_financials(): void
    {
        $tech = User::factory()->technician()->create();

        $this->mock(OdooServiceInterface::class)
            ->shouldNotReceive('getCustomerOrders');

        $this->actingAs($tech, 'sanctum')
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.financials', null);
    }

    public function test_customer_without_odoo_id_gets_null_financials(): void
    {
        $customer = User::factory()->customer()->create(['odoo_id' => null]);

        $this->mock(OdooServiceInterface::class)
            ->shouldNotReceive('getCustomerOrders');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.financials', null);
    }

    public function test_customer_with_odoo_id_gets_financials(): void
    {
        $customer = User::factory()->customer()->withOdoo()->create();

        $this->mock(OdooServiceInterface::class)
            ->shouldReceive('getCustomerOrders')
            ->once()
            ->andReturn([
                ['amount_total' => 100.0, 'amount_due' => 50.0],
                ['amount_total' => 200.0, 'amount_due' => 0.0],
            ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.financials.summary.total_amount', 300)
            ->assertJsonPath('data.financials.summary.total_due', 50);
    }

    public function test_update_profile_changes_name_and_phone(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/update-profile', [
                'name'  => 'New Name',
                'phone' => '96899333333',
            ])
            ->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }
}
