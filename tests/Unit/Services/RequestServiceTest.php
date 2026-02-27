<?php

namespace Tests\Unit\Services;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Request;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Odoo\OdooServiceInterface;
use App\Services\RequestService;
use Mockery\MockInterface;
use Tests\TestCase;

class RequestServiceTest extends TestCase
{
    private RequestService $service;
    private MockInterface $notification;
    private MockInterface $odoo;
    private User $admin;
    private User $customer;
    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notification = $this->mock(NotificationService::class);
        $this->notification->shouldIgnoreMissing();

        $this->odoo = $this->mock(OdooServiceInterface::class);
        $this->odoo->shouldIgnoreMissing();

        $this->service = app(RequestService::class);

        $this->admin      = User::factory()->admin()->create();
        $this->customer   = User::factory()->customer()->create();
        $this->technician = User::factory()->technician()->create();
    }

    // ── createRequest ────────────────────────────────────────────────────────

    public function test_create_service_request_adds_t_prefix(): void
    {
        $req = $this->service->createRequest(
            $this->customer,
            [
                'invoice_number' => '12345',
                'service_type'   => 'maintenance',
                'address'        => 'Test St',
                'latitude'       => 23.5,
                'longitude'      => 58.4,
                'scheduled_at'   => now()->addDays(2)->format('Y-m-d'),
            ],
            RequestType::Service
        );

        $this->assertStringStartsWith('T-', $req->invoice_number);
        $this->assertEquals('T-12345', $req->invoice_number);
        $this->assertEquals('service', $req->type->value);
        $this->assertEquals('pending', $req->status->value);
        $this->assertEquals($this->customer->id, $req->user_id);
    }

    public function test_create_installation_request_adds_b_prefix(): void
    {
        $req = $this->service->createRequest(
            $this->customer,
            [
                'invoice_number' => '99999',
                'product_type'   => 'AC Unit',
                'address'        => 'Test St',
                'latitude'       => 23.5,
                'longitude'      => 58.4,
                'scheduled_at'   => now()->addDays(2)->format('Y-m-d'),
            ],
            RequestType::Installation
        );

        $this->assertEquals('B-99999', $req->invoice_number);
        $this->assertEquals('installation', $req->type->value);
    }

    public function test_create_request_notifies_admins(): void
    {
        $this->notification->shouldReceive('notifyAdmins')->once();

        $this->service->createRequest(
            $this->customer,
            [
                'service_type' => 'repair',
                'address'      => 'Test',
                'latitude'     => 23.0,
                'longitude'    => 58.0,
                'scheduled_at' => now()->addDays(1)->format('Y-m-d'),
            ],
            RequestType::Service
        );
    }

    public function test_create_request_strips_existing_prefix(): void
    {
        $req = $this->service->createRequest(
            $this->customer,
            ['invoice_number' => 'T-12345', 'service_type' => 'maintenance', 'address' => 'X', 'latitude' => 23.0, 'longitude' => 58.0, 'scheduled_at' => now()->addDays(1)->format('Y-m-d')],
            RequestType::Service
        );

        // Should not double-prefix to T-T-12345
        $this->assertEquals('T-12345', $req->invoice_number);
    }

    public function test_create_request_without_invoice_number(): void
    {
        $req = $this->service->createRequest(
            $this->customer,
            ['service_type' => 'inspection', 'address' => 'Test', 'latitude' => 23.0, 'longitude' => 58.0, 'scheduled_at' => now()->addDays(1)->format('Y-m-d')],
            RequestType::Service
        );

        $this->assertNull($req->invoice_number);
    }

    // ── assignTechnician ─────────────────────────────────────────────────────

    public function test_assign_technician_updates_status_and_technician_id(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $result = $this->service->assignTechnician($req, $this->technician->id);

        $this->assertEquals($this->technician->id, $result->technician_id);
        $this->assertEquals(RequestStatus::Assigned->value, $result->status->value);
    }

    public function test_assign_technician_notifies_both_parties(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->notification->shouldReceive('notifyUser')->twice();

        $this->service->assignTechnician($req, $this->technician->id);
    }

    // ── updateStatus ─────────────────────────────────────────────────────────

    public function test_update_to_completed_sets_completed_at(): void
    {
        $req = Request::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        // Add rating so technician block doesn't apply (actor is admin here)
        $result = $this->service->updateStatus($req, RequestStatus::Completed, $this->admin);

        $this->assertEquals(RequestStatus::Completed->value, $result->status->value);
        $this->assertNotNull($result->completed_at);
    }

    public function test_non_completed_status_does_not_set_completed_at(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $result = $this->service->updateStatus($req, RequestStatus::InProgress, $this->admin);

        $this->assertEquals(RequestStatus::InProgress->value, $result->status->value);
        $this->assertNull($result->completed_at);
    }

    public function test_update_status_notifies_customer(): void
    {
        $req = Request::factory()->forUser($this->customer)->create();

        $this->notification->shouldReceive('notifyUser')->atLeast()->once();

        $this->service->updateStatus($req, RequestStatus::InProgress, $this->admin);
    }

    public function test_admin_updating_status_also_notifies_technician(): void
    {
        $req = Request::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        // Admin update should notify both customer and technician (2 notifyUser calls)
        $this->notification->shouldReceive('notifyUser')->twice();

        $this->service->updateStatus($req, RequestStatus::InProgress, $this->admin);
    }

    public function test_technician_updating_status_only_notifies_customer(): void
    {
        $req = Request::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        // Technician update should NOT notify the technician (only customer)
        $this->notification->shouldReceive('notifyUser')->once();

        $this->service->updateStatus($req, RequestStatus::OnWay, $this->technician);
    }

    public function test_technician_cannot_complete_without_rating(): void
    {
        $req = Request::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        $this->expectException(\Exception::class);

        $this->service->updateStatus($req, RequestStatus::Completed, $this->technician);
    }

    public function test_technician_can_complete_after_rating_exists(): void
    {
        $req = Request::factory()
            ->forUser($this->customer)
            ->assignedTo($this->technician)
            ->create();

        $req->rating()->create([
            'user_id'        => $this->customer->id,
            'service_rating' => 5,
        ]);

        $result = $this->service->updateStatus($req->fresh(), RequestStatus::Completed, $this->technician);

        $this->assertEquals(RequestStatus::Completed->value, $result->status->value);
    }

    // ── checkTechnicianAvailability ──────────────────────────────────────────

    public function test_technician_is_available_when_no_active_requests(): void
    {
        $available = $this->service->checkTechnicianAvailability(
            $this->technician->id,
            now()->addDays(1)->format('Y-m-d H:i:s'),
            now()->addDays(2)->format('Y-m-d H:i:s')
        );

        $this->assertTrue($available);
    }

    public function test_technician_is_unavailable_when_has_overlapping_active_request(): void
    {
        Request::factory()->forUser($this->customer)->create([
            'technician_id' => $this->technician->id,
            'status'        => 'assigned',
            'scheduled_at'  => now()->addDays(1)->format('Y-m-d'),
            'end_date'      => now()->addDays(3)->format('Y-m-d'),
        ]);

        $available = $this->service->checkTechnicianAvailability(
            $this->technician->id,
            now()->addDays(1)->format('Y-m-d H:i:s'),
            now()->addDays(2)->format('Y-m-d H:i:s')
        );

        $this->assertFalse($available);
    }
}
