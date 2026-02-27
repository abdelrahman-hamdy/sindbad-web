<?php

namespace Tests\Unit\Policies;

use App\Models\Request;
use App\Models\User;
use App\Policies\RequestPolicy;
use Tests\TestCase;

class RequestPolicyTest extends TestCase
{
    private RequestPolicy $policy;
    private User $admin;
    private User $customer;
    private User $technician;
    private User $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy        = new RequestPolicy();
        $this->admin         = User::factory()->admin()->create();
        $this->customer      = User::factory()->customer()->create();
        $this->technician    = User::factory()->technician()->create();
        $this->otherCustomer = User::factory()->customer()->create();
    }

    // ── viewAny ──────────────────────────────────────────────────────────────

    public function test_anyone_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
        $this->assertTrue($this->policy->viewAny($this->customer));
        $this->assertTrue($this->policy->viewAny($this->technician));
    }

    // ── view ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_request(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->assertTrue($this->policy->view($this->admin, $req));
    }

    public function test_customer_can_view_own_request(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->assertTrue($this->policy->view($this->customer, $req));
    }

    public function test_customer_cannot_view_others_request(): void
    {
        $req = Request::factory()->forUser($this->otherCustomer)->create();

        $this->assertFalse($this->policy->view($this->customer, $req));
    }

    public function test_assigned_technician_can_view_request(): void
    {
        $req = Request::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        $this->assertTrue($this->policy->view($this->technician, $req));
    }

    public function test_unassigned_technician_cannot_view_request(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        // technician has no relation to this request
        $this->assertFalse($this->policy->view($this->technician, $req));
    }

    // ── create ───────────────────────────────────────────────────────────────

    public function test_all_roles_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
        $this->assertTrue($this->policy->create($this->customer));
        $this->assertTrue($this->policy->create($this->technician));
    }

    // ── update ───────────────────────────────────────────────────────────────

    public function test_only_admin_can_update(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->assertTrue($this->policy->update($this->admin, $req));
        $this->assertFalse($this->policy->update($this->customer, $req));
        $this->assertFalse($this->policy->update($this->technician, $req));
    }

    // ── delete ───────────────────────────────────────────────────────────────

    public function test_only_admin_can_delete(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->assertTrue($this->policy->delete($this->admin, $req));
        $this->assertFalse($this->policy->delete($this->customer, $req));
        $this->assertFalse($this->policy->delete($this->technician, $req));
    }

    // ── restore / forceDelete ─────────────────────────────────────────────────

    public function test_only_admin_can_restore(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->assertTrue($this->policy->restore($this->admin, $req));
        $this->assertFalse($this->policy->restore($this->customer, $req));
    }

    public function test_only_admin_can_force_delete(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->assertTrue($this->policy->forceDelete($this->admin, $req));
        $this->assertFalse($this->policy->forceDelete($this->customer, $req));
    }

    public function test_delete_works_without_model_for_bulk_operations(): void
    {
        // authorize('delete', ModelClass::class) passes no model instance
        $this->assertTrue($this->policy->delete($this->admin));
        $this->assertFalse($this->policy->delete($this->customer));
    }
}
