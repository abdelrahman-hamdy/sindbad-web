<?php

namespace Database\Seeders;

use App\Enums\ManualOrderStatus;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\ServiceType;
use App\Models\ManualOrder;
use App\Models\Rating;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Customer ──────────────────────────────────────────────────────────
        $customer = User::where('role', 'customer')->first();
        if (! $customer) {
            $customer = User::create([
                'name'      => 'Ahmed Al Balushi',
                'phone'     => '96890000001',
                'role'      => 'customer',
                'is_active' => true,
                'password'  => Hash::make('password'),
            ]);
        }

        // ── Technician ────────────────────────────────────────────────────────
        $tech = User::where('role', 'technician')->first();
        if (! $tech) {
            $tech = User::create([
                'name'      => 'Khalid Al Rashdi',
                'phone'     => '96891111111',
                'role'      => 'technician',
                'is_active' => true,
                'password'  => Hash::make('password'),
            ]);
        }

        // ── Service Requests ──────────────────────────────────────────────────
        $serviceRequests = [
            [
                'invoice_number' => 'T-2026-001',
                'service_type'   => ServiceType::Maintenance->value,
                'description'    => 'Annual AC maintenance and filter cleaning',
                'status'         => RequestStatus::Completed->value,
                'scheduled_at'   => now()->subDays(60)->format('Y-m-d'),
                'completed_at'   => now()->subDays(59),
                'technician_id'  => $tech->id,
                'technician_accepted_at' => now()->subDays(60),
                'withRating'     => [4, 5, 'Very professional technician. Arrived on time and did a thorough job.', 'Friend'],
            ],
            [
                'invoice_number' => 'T-2026-002',
                'service_type'   => ServiceType::Repair->value,
                'description'    => 'AC not cooling — gas refill and leak check',
                'status'         => RequestStatus::Completed->value,
                'scheduled_at'   => now()->subDays(40)->format('Y-m-d'),
                'completed_at'   => now()->subDays(39),
                'technician_id'  => $tech->id,
                'technician_accepted_at' => now()->subDays(40),
                'withRating'     => [3, 4, 'Fixed the issue but took longer than expected.', 'Social Media'],
            ],
            [
                'invoice_number' => 'T-2026-003',
                'service_type'   => ServiceType::Inspection->value,
                'description'    => 'Pre-summer inspection for 3 AC units',
                'status'         => RequestStatus::Completed->value,
                'scheduled_at'   => now()->subDays(20)->format('Y-m-d'),
                'completed_at'   => now()->subDays(19),
                'technician_id'  => $tech->id,
                'technician_accepted_at' => now()->subDays(20),
                'withRating'     => [5, 5, 'Excellent! Very detailed inspection report provided.', 'Google'],
            ],
            [
                'invoice_number' => 'T-2026-004',
                'service_type'   => ServiceType::Maintenance->value,
                'description'    => 'Quarterly maintenance visit — bedroom units',
                'status'         => RequestStatus::Assigned->value,
                'scheduled_at'   => now()->addDays(3)->format('Y-m-d'),
                'completed_at'   => null,
                'technician_id'  => $tech->id,
                'technician_accepted_at' => now()->subDay(),
                'withRating'     => null,
            ],
            [
                'invoice_number' => 'T-2026-005',
                'service_type'   => ServiceType::Repair->value,
                'description'    => 'Strange noise from living room AC unit',
                'status'         => RequestStatus::Pending->value,
                'scheduled_at'   => now()->addDays(7)->format('Y-m-d'),
                'completed_at'   => null,
                'technician_id'  => null,
                'technician_accepted_at' => null,
                'withRating'     => null,
            ],
        ];

        foreach ($serviceRequests as $data) {
            $rating = $data['withRating'];
            unset($data['withRating']);

            $request = Request::create(array_merge($data, [
                'type'      => RequestType::Service->value,
                'user_id'   => $customer->id,
                'address'   => 'Al Khuwair, Muscat, Oman',
                'latitude'  => 23.5880,
                'longitude' => 58.3829,
            ]));

            if ($rating) {
                Rating::create([
                    'request_id'     => $request->id,
                    'user_id'        => $customer->id,
                    'product_rating' => $rating[0],
                    'service_rating' => $rating[1],
                    'customer_notes' => $rating[2],
                    'how_found_us'   => $rating[3],
                ]);
            }
        }

        // ── Installation Requests ─────────────────────────────────────────────
        $installRequests = [
            [
                'invoice_number' => 'B-2026-001',
                'product_type'   => 'Split AC 2 Ton',
                'quantity'       => 2,
                'is_site_ready'  => true,
                'notes'          => 'Two units for master bedroom and guest room',
                'status'         => RequestStatus::Completed->value,
                'scheduled_at'   => now()->subDays(55)->format('Y-m-d'),
                'completed_at'   => now()->subDays(54),
                'technician_id'  => $tech->id,
                'technician_accepted_at' => now()->subDays(55),
                'withRating'     => [5, 4, 'Clean installation, no mess left behind.', 'Word of Mouth'],
            ],
            [
                'invoice_number' => 'B-2026-002',
                'product_type'   => 'Cassette AC 3 Ton',
                'quantity'       => 1,
                'is_site_ready'  => true,
                'notes'          => 'Office ceiling cassette unit',
                'status'         => RequestStatus::Completed->value,
                'scheduled_at'   => now()->subDays(30)->format('Y-m-d'),
                'completed_at'   => now()->subDays(29),
                'technician_id'  => $tech->id,
                'technician_accepted_at' => now()->subDays(30),
                'withRating'     => [4, 5, 'Professional team. Completed faster than expected.', 'Google'],
            ],
            [
                'invoice_number' => 'B-2026-003',
                'product_type'   => 'Ducted AC 5 Ton',
                'quantity'       => 1,
                'is_site_ready'  => false,
                'notes'          => 'New villa — ducted system for entire house',
                'status'         => RequestStatus::Pending->value,
                'scheduled_at'   => now()->addDays(14)->format('Y-m-d'),
                'completed_at'   => null,
                'technician_id'  => null,
                'technician_accepted_at' => null,
                'withRating'     => null,
            ],
        ];

        foreach ($installRequests as $data) {
            $rating = $data['withRating'];
            unset($data['withRating']);

            $request = Request::create(array_merge($data, [
                'type'    => RequestType::Installation->value,
                'user_id' => $customer->id,
                'address' => 'Al Mouj, Muscat, Oman',
                'latitude'  => 23.6139,
                'longitude' => 58.5922,
            ]));

            if ($rating) {
                Rating::create([
                    'request_id'     => $request->id,
                    'user_id'        => $customer->id,
                    'product_rating' => $rating[0],
                    'service_rating' => $rating[1],
                    'customer_notes' => $rating[2],
                    'how_found_us'   => $rating[3],
                ]);
            }
        }

        // ── Manual Orders (Invoices) ───────────────────────────────────────────
        $invoices = [
            [
                'invoice_number'     => 'INV-2026-0041',
                'quotation_template' => 'QT-2026-0041',
                'total_amount'       => 185.000,
                'paid_amount'        => 185.000,
                'remaining_amount'   => 0.000,
                'status'             => ManualOrderStatus::Paid->value,
                'order_date'         => now()->subDays(58),
            ],
            [
                'invoice_number'     => 'INV-2026-0067',
                'quotation_template' => 'QT-2026-0067',
                'total_amount'       => 220.500,
                'paid_amount'        => 220.500,
                'remaining_amount'   => 0.000,
                'status'             => ManualOrderStatus::Paid->value,
                'order_date'         => now()->subDays(38),
            ],
            [
                'invoice_number'     => 'INV-2026-0089',
                'quotation_template' => 'QT-2026-0089',
                'total_amount'       => 340.000,
                'paid_amount'        => 170.000,
                'remaining_amount'   => 170.000,
                'status'             => ManualOrderStatus::Partial->value,
                'order_date'         => now()->subDays(28),
            ],
            [
                'invoice_number'     => 'INV-2026-0102',
                'quotation_template' => null,
                'total_amount'       => 95.750,
                'paid_amount'        => 95.750,
                'remaining_amount'   => 0.000,
                'status'             => ManualOrderStatus::Paid->value,
                'order_date'         => now()->subDays(18),
            ],
            [
                'invoice_number'     => 'INV-2026-0118',
                'quotation_template' => 'QT-2026-0118',
                'total_amount'       => 450.000,
                'paid_amount'        => 0.000,
                'remaining_amount'   => 450.000,
                'status'             => ManualOrderStatus::Partial->value,
                'order_date'         => now()->subDays(5),
            ],
        ];

        foreach ($invoices as $invoice) {
            ManualOrder::create(array_merge($invoice, ['user_id' => $customer->id]));
        }

        $this->command->info("Seeded demo data for customer: {$customer->name} (ID: {$customer->id})");
        $this->command->info("  → 5 service requests (3 completed, 1 assigned, 1 pending)");
        $this->command->info("  → 3 installation requests (2 completed, 1 pending)");
        $this->command->info("  → 5 ratings on completed requests");
        $this->command->info("  → 5 manual invoices");
    }
}
