<?php

namespace Database\Seeders;

use App\Enums\ManualOrderStatus;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\ServiceType;
use App\Models\ManualOrder;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\Request;
use App\Models\TechnicianLocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FullDemoSeeder extends Seeder
{
    // Muscat neighbourhoods with realistic coordinates
    private array $locations = [
        ['area' => 'Al Khuwair, Muscat',     'lat' => 23.5880, 'lng' => 58.3829],
        ['area' => 'Qurum, Muscat',           'lat' => 23.5935, 'lng' => 58.3888],
        ['area' => 'Bausher, Muscat',         'lat' => 23.5754, 'lng' => 58.3614],
        ['area' => 'Al Ghubra, Muscat',       'lat' => 23.5959, 'lng' => 58.3688],
        ['area' => 'Al Azaiba, Muscat',       'lat' => 23.6050, 'lng' => 58.4253],
        ['area' => 'Al Mouj, Muscat',         'lat' => 23.6139, 'lng' => 58.5922],
        ['area' => 'Muttrah, Muscat',         'lat' => 23.6200, 'lng' => 58.5932],
        ['area' => 'Ruwi, Muscat',            'lat' => 23.6103, 'lng' => 58.5936],
        ['area' => 'Al Seeb, Muscat',         'lat' => 23.6806, 'lng' => 58.1889],
        ['area' => 'Shatti Al Qurum, Muscat', 'lat' => 23.5888, 'lng' => 58.3820],
    ];

    public function run(): void
    {
        // ── Technicians ───────────────────────────────────────────────────────
        $technicians = $this->seedTechnicians();

        // ── Customers ─────────────────────────────────────────────────────────
        $customers = $this->seedCustomers();

        // ── Requests (all statuses + both types) ──────────────────────────────
        $this->seedRequests($customers, $technicians);

        // ── Notifications ─────────────────────────────────────────────────────
        $this->seedNotifications($customers, $technicians);

        // ── Technician Locations ──────────────────────────────────────────────
        $this->seedTechnicianLocations($technicians);

        $this->command->newLine();
        $this->command->info('✅ FullDemoSeeder complete.');
        $this->command->table(
            ['Role', 'Count', 'Phone / Password'],
            [
                ['Customer',   count($customers),   'see below / password'],
                ['Technician', count($technicians), 'see below / password'],
            ]
        );

        $rows = [];
        foreach ($customers as $c) {
            $rows[] = ['customer', $c->name, $c->phone];
        }
        foreach ($technicians as $t) {
            $rows[] = ['technician', $t->name, $t->phone];
        }
        $this->command->table(['Role', 'Name', 'Phone'], $rows);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Technicians
    // ─────────────────────────────────────────────────────────────────────────
    private function seedTechnicians(): array
    {
        $data = [
            [
                'name'      => 'Yusuf Al Rashdi',
                'phone'     => '96896789012',
                'is_active' => true,
                'odoo_id'   => null,
            ],
            [
                'name'      => 'Omar Al Habsi',
                'phone'     => '96897890123',
                'is_active' => true,
                'odoo_id'   => null,
            ],
            [
                'name'      => 'Hamad Al Siyabi',
                'phone'     => '96898901234',
                'is_active' => true,
                'odoo_id'   => null,
            ],
        ];

        $technicians = [];
        foreach ($data as $row) {
            $tech = User::firstOrCreate(
                ['phone' => $row['phone']],
                array_merge($row, [
                    'role'     => 'technician',
                    'password' => Hash::make('password'),
                ])
            );
            if (! $tech->hasRole('technician')) {
                $tech->assignRole('technician');
            }
            $technicians[] = $tech;
        }

        return $technicians;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Customers  (with Odoo IDs as if synced from Odoo)
    // ─────────────────────────────────────────────────────────────────────────
    private function seedCustomers(): array
    {
        $data = [
            // Primary test customer — has requests in EVERY status
            [
                'name'                => 'Ahmed Al Balushi',
                'phone'               => '96890000001',
                'odoo_id'             => 12453,
                'invoice_number'      => 'SO-12453',
                'quotation_template'  => 'Split AC Package',
                'is_active'           => true,
            ],
            // Customer with mainly installation requests
            [
                'name'                => 'Fatima Al Zadjali',
                'phone'               => '96892345678',
                'odoo_id'             => 18721,
                'invoice_number'      => 'SO-18721',
                'quotation_template'  => 'Cassette AC Package',
                'is_active'           => true,
            ],
            // New customer — only pending request
            [
                'name'                => 'Mohammed Al Hinai',
                'phone'               => '96893456789',
                'odoo_id'             => 24156,
                'invoice_number'      => 'SO-24156',
                'quotation_template'  => null,
                'is_active'           => true,
            ],
            // Customer with an active on_way + in_progress request
            [
                'name'                => 'Khalid Al Mawali',
                'phone'               => '96894567890',
                'odoo_id'             => 31782,
                'invoice_number'      => 'SO-31782',
                'quotation_template'  => 'Ducted AC Package',
                'is_active'           => true,
            ],
            // Customer with a canceled request + completed history
            [
                'name'                => 'Sara Al Farsi',
                'phone'               => '96895678901',
                'odoo_id'             => 9853,
                'invoice_number'      => 'SO-9853',
                'quotation_template'  => null,
                'is_active'           => true,
            ],
        ];

        $customers = [];
        foreach ($data as $row) {
            $customer = User::firstOrCreate(
                ['phone' => $row['phone']],
                array_merge($row, [
                    'role'     => 'customer',
                    'password' => Hash::make('password'),
                ])
            );
            if (! $customer->hasRole('customer')) {
                $customer->assignRole('customer');
            }
            $customers[] = $customer;
        }

        return $customers;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requests — every status, both types
    // ─────────────────────────────────────────────────────────────────────────
    private function seedRequests(array $customers, array $technicians): void
    {
        [$tech1, $tech2, $tech3] = $technicians;
        [$ahmed, $fatima, $mohammed, $khalid, $sara] = $customers;

        // ── Ahmed: one request per status ─────────────────────────────────────
        $this->makeService($ahmed, [
            'invoice_number' => 'T-2026-001',
            'service_type'   => ServiceType::Maintenance,
            'description'    => 'Annual maintenance for 4 split AC units — filter cleaning and gas check',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(65)->toDateString(),
            'completed_at'   => now()->subDays(63),
            'technician_id'  => $tech1->id,
            'technician_accepted_at' => now()->subDays(65),
            'task_start_time'        => now()->subDays(64),
            'task_end_time'          => now()->subDays(63),
        ], $this->loc(0), [5, 5, 'Outstanding service. Arrived on time and was very thorough.', 'Friend']);

        $this->makeService($ahmed, [
            'invoice_number' => 'T-2026-002',
            'service_type'   => ServiceType::Repair,
            'description'    => 'AC leaking water — drain pipe blockage in living room unit',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(40)->toDateString(),
            'completed_at'   => now()->subDays(38),
            'technician_id'  => $tech2->id,
            'technician_accepted_at' => now()->subDays(40),
            'task_start_time'        => now()->subDays(39),
            'task_end_time'          => now()->subDays(38),
        ], $this->loc(1), [4, 4, 'Fixed the leak. Took a bit longer than expected but good result.', 'Social Media']);

        $this->makeService($ahmed, [
            'invoice_number' => 'T-2026-003',
            'service_type'   => ServiceType::Inspection,
            'description'    => 'Pre-summer inspection for all AC units before hot season',
            'status'         => RequestStatus::Assigned,
            'scheduled_at'   => now()->addDays(2)->toDateString(),
            'technician_id'  => $tech1->id,
            'technician_accepted_at' => now()->subHours(3),
        ], $this->loc(0));

        $this->makeService($ahmed, [
            'invoice_number' => 'T-2026-004',
            'service_type'   => ServiceType::Maintenance,
            'description'    => 'Quarterly maintenance — bedroom and kitchen units',
            'status'         => RequestStatus::OnWay,
            'scheduled_at'   => now()->toDateString(),
            'technician_id'  => $tech2->id,
            'technician_accepted_at' => now()->subHours(2),
        ], $this->loc(9));

        $this->makeService($ahmed, [
            'invoice_number' => 'T-2026-005',
            'service_type'   => ServiceType::Repair,
            'description'    => 'AC making loud noise — compressor check required',
            'status'         => RequestStatus::InProgress,
            'scheduled_at'   => now()->toDateString(),
            'technician_id'  => $tech3->id,
            'technician_accepted_at' => now()->subHours(4),
            'task_start_time'        => now()->subHours(1),
        ], $this->loc(2));

        $this->makeService($ahmed, [
            'invoice_number' => 'T-2026-006',
            'service_type'   => ServiceType::Inspection,
            'description'    => 'New apartment inspection — checking all AC connections',
            'status'         => RequestStatus::Pending,
            'scheduled_at'   => now()->addDays(5)->toDateString(),
        ], $this->loc(4));

        $this->makeService($ahmed, [
            'invoice_number' => 'T-2026-007',
            'service_type'   => ServiceType::Repair,
            'description'    => 'Emergency repair — AC stopped working completely',
            'status'         => RequestStatus::Canceled,
            'scheduled_at'   => now()->subDays(10)->toDateString(),
            'technician_id'  => $tech1->id,
        ], $this->loc(3));

        // Ahmed — installation requests
        $this->makeInstallation($ahmed, [
            'invoice_number' => 'B-2026-001',
            'product_type'   => 'Split AC 1.5 Ton – Samsung Wind-Free',
            'quantity'       => 3,
            'is_site_ready'  => true,
            'description'    => 'Three units for bedrooms — wiring already in place',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(50)->toDateString(),
            'completed_at'   => now()->subDays(48),
            'technician_id'  => $tech1->id,
            'technician_accepted_at' => now()->subDays(50),
            'task_start_time'        => now()->subDays(49),
            'task_end_time'          => now()->subDays(48),
        ], $this->loc(5), [5, 4, 'Neat and clean installation. All three units work perfectly.', 'Word of Mouth']);

        $this->makeInstallation($ahmed, [
            'invoice_number' => 'B-2026-002',
            'product_type'   => 'Cassette AC 2 Ton – Carrier',
            'quantity'       => 1,
            'is_site_ready'  => false,
            'description'    => 'Office ceiling cassette — site prep still in progress',
            'status'         => RequestStatus::Pending,
            'scheduled_at'   => now()->addDays(10)->toDateString(),
        ], $this->loc(7));

        // ── Manual Orders for Ahmed ────────────────────────────────────────────
        $this->seedManualOrders($ahmed, [
            ['INV-2026-0041', 'QT-2026-0041', 185.000, 185.000, 0.000,   'paid',    60],
            ['INV-2026-0067', 'QT-2026-0067', 220.500, 220.500, 0.000,   'paid',    38],
            ['INV-2026-0089', 'QT-2026-0089', 340.000, 170.000, 170.000, 'partial', 28],
            ['INV-2026-0102', null,            95.750,  95.750,  0.000,   'paid',    18],
            ['INV-2026-0118', 'QT-2026-0118', 450.000, 0.000,   450.000, 'partial',  5],
        ]);

        // ── Fatima: installation-heavy ─────────────────────────────────────────
        $this->makeInstallation($fatima, [
            'invoice_number' => 'B-2026-010',
            'product_type'   => 'Split AC 2 Ton – LG Dual Inverter',
            'quantity'       => 2,
            'is_site_ready'  => true,
            'description'    => 'Master bedroom and guest room',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(45)->toDateString(),
            'completed_at'   => now()->subDays(43),
            'technician_id'  => $tech2->id,
            'technician_accepted_at' => now()->subDays(45),
            'task_start_time'        => now()->subDays(44),
            'task_end_time'          => now()->subDays(43),
        ], $this->loc(6), [4, 5, 'Very professional. Cleaned up everything after installation.', 'Google']);

        $this->makeInstallation($fatima, [
            'invoice_number' => 'B-2026-011',
            'product_type'   => 'Cassette AC 3 Ton – Midea',
            'quantity'       => 1,
            'is_site_ready'  => true,
            'description'    => 'Majlis ceiling cassette unit',
            'status'         => RequestStatus::Assigned,
            'scheduled_at'   => now()->addDays(3)->toDateString(),
            'technician_id'  => $tech3->id,
            'technician_accepted_at' => now()->subHours(6),
        ], $this->loc(8));

        $this->makeInstallation($fatima, [
            'invoice_number' => 'B-2026-012',
            'product_type'   => 'Ducted AC 5 Ton – Daikin VRV',
            'quantity'       => 1,
            'is_site_ready'  => false,
            'description'    => 'New villa — full ducted system for 6 rooms',
            'status'         => RequestStatus::Pending,
            'scheduled_at'   => now()->addDays(20)->toDateString(),
        ], $this->loc(9));

        $this->makeService($fatima, [
            'invoice_number' => 'T-2026-020',
            'service_type'   => ServiceType::Maintenance,
            'description'    => 'Post-summer clean-up maintenance for all AC units',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(15)->toDateString(),
            'completed_at'   => now()->subDays(14),
            'technician_id'  => $tech1->id,
            'technician_accepted_at' => now()->subDays(15),
            'task_start_time'        => now()->subDays(15)->addHours(9),
            'task_end_time'          => now()->subDays(15)->addHours(12),
        ], $this->loc(6), [5, 5, 'Best maintenance team! Highly recommended.', 'Friend']);

        $this->seedManualOrders($fatima, [
            ['INV-2026-0210', 'QT-2026-0210', 1250.000, 1250.000, 0.000,   'paid',    43],
            ['INV-2026-0225', 'QT-2026-0225', 380.500,  190.250,  190.250, 'partial',  8],
        ]);

        // ── Mohammed: new customer, pending only ──────────────────────────────
        $this->makeService($mohammed, [
            'invoice_number' => 'T-2026-030',
            'service_type'   => ServiceType::Repair,
            'description'    => 'AC unit in study room not cooling — gas might be low',
            'status'         => RequestStatus::Pending,
            'scheduled_at'   => now()->addDays(1)->toDateString(),
        ], $this->loc(2));

        $this->makeInstallation($mohammed, [
            'invoice_number' => 'B-2026-030',
            'product_type'   => 'Split AC 1 Ton – Panasonic Inverter',
            'quantity'       => 1,
            'is_site_ready'  => true,
            'description'    => 'Small room near entrance — first time installation',
            'status'         => RequestStatus::Pending,
            'scheduled_at'   => now()->addDays(4)->toDateString(),
        ], $this->loc(3));

        $this->seedManualOrders($mohammed, [
            ['INV-2026-0310', null, 150.000, 0.000, 150.000, 'partial', 2],
        ]);

        // ── Khalid: on_way + in_progress active requests ───────────────────────
        $this->makeService($khalid, [
            'invoice_number' => 'T-2026-040',
            'service_type'   => ServiceType::Maintenance,
            'description'    => 'Maintenance for 2 split units in villa — monthly contract',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(30)->toDateString(),
            'completed_at'   => now()->subDays(29),
            'technician_id'  => $tech2->id,
            'technician_accepted_at' => now()->subDays(30),
            'task_start_time'        => now()->subDays(30)->addHours(8),
            'task_end_time'          => now()->subDays(30)->addHours(11),
        ], $this->loc(4), [4, 5, 'Consistent and reliable service every month.', 'Word of Mouth']);

        $this->makeService($khalid, [
            'invoice_number' => 'T-2026-041',
            'service_type'   => ServiceType::Repair,
            'description'    => 'Compressor issue — unit keeps tripping the breaker',
            'status'         => RequestStatus::InProgress,
            'scheduled_at'   => now()->toDateString(),
            'technician_id'  => $tech2->id,
            'technician_accepted_at' => now()->subHours(3),
            'task_start_time'        => now()->subHours(1),
        ], $this->loc(4));

        $this->makeInstallation($khalid, [
            'invoice_number' => 'B-2026-040',
            'product_type'   => 'Ducted AC 4 Ton – Haier',
            'quantity'       => 1,
            'is_site_ready'  => true,
            'description'    => 'Replacement of old ducted system — same duct work',
            'status'         => RequestStatus::OnWay,
            'scheduled_at'   => now()->toDateString(),
            'technician_id'  => $tech3->id,
            'technician_accepted_at' => now()->subHours(2),
        ], $this->loc(8));

        $this->seedManualOrders($khalid, [
            ['INV-2026-0410', 'QT-2026-0410', 2800.000, 1400.000, 1400.000, 'partial', 30],
            ['INV-2026-0411', 'QT-2026-0411', 320.000,  320.000,  0.000,    'paid',    28],
        ]);

        // ── Sara: completed history + one canceled ─────────────────────────────
        $this->makeService($sara, [
            'invoice_number' => 'T-2026-050',
            'service_type'   => ServiceType::Inspection,
            'description'    => 'Inspection before moving into new apartment',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(90)->toDateString(),
            'completed_at'   => now()->subDays(89),
            'technician_id'  => $tech1->id,
            'technician_accepted_at' => now()->subDays(90),
            'task_start_time'        => now()->subDays(90)->addHours(10),
            'task_end_time'          => now()->subDays(90)->addHours(11),
        ], $this->loc(1), [3, 4, 'Good inspection but wish report was more detailed.', 'Google']);

        $this->makeService($sara, [
            'invoice_number' => 'T-2026-051',
            'service_type'   => ServiceType::Maintenance,
            'description'    => 'Annual maintenance package for 3 units',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(25)->toDateString(),
            'completed_at'   => now()->subDays(23),
            'technician_id'  => $tech2->id,
            'technician_accepted_at' => now()->subDays(25),
            'task_start_time'        => now()->subDays(24)->addHours(9),
            'task_end_time'          => now()->subDays(23)->addHours(17),
        ], $this->loc(1), [5, 5, 'Excellent team! All 3 units cooling perfectly now.', 'Friend']);

        $this->makeService($sara, [
            'invoice_number' => 'T-2026-052',
            'service_type'   => ServiceType::Repair,
            'description'    => 'Emergency repair — AC stopped, requested urgent visit',
            'status'         => RequestStatus::Canceled,
            'scheduled_at'   => now()->subDays(5)->toDateString(),
            'technician_id'  => null,
        ], $this->loc(0));

        $this->makeInstallation($sara, [
            'invoice_number' => 'B-2026-050',
            'product_type'   => 'Split AC 2 Ton – Toshiba Inverter',
            'quantity'       => 2,
            'is_site_ready'  => true,
            'description'    => 'Replacing old units in living room and dining area',
            'status'         => RequestStatus::Completed,
            'scheduled_at'   => now()->subDays(60)->toDateString(),
            'completed_at'   => now()->subDays(59),
            'technician_id'  => $tech1->id,
            'technician_accepted_at' => now()->subDays(60),
            'task_start_time'        => now()->subDays(60)->addHours(8),
            'task_end_time'          => now()->subDays(60)->addHours(14),
        ], $this->loc(5), [4, 4, 'Good work. Took a bit longer but result is great.', 'Social Media']);

        $this->seedManualOrders($sara, [
            ['INV-2026-0510', 'QT-2026-0510', 720.000, 720.000, 0.000,   'paid',    88],
            ['INV-2026-0511', 'QT-2026-0511', 420.000, 420.000, 0.000,   'paid',    22],
            ['INV-2026-0512', null,            180.000, 90.000,  90.000,  'partial',  3],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notifications — per user
    // ─────────────────────────────────────────────────────────────────────────
    private function seedNotifications(array $customers, array $technicians): void
    {
        [$ahmed, $fatima, $mohammed, $khalid, $sara] = $customers;
        [$tech1, $tech2, $tech3] = $technicians;

        // Ahmed notifications
        $this->notify($ahmed, 'تم تعيين فني لطلبك', 'تم تعيين الفني يوسف الرشدي لطلب الصيانة T-2026-003. سيتم التواصل معك قريباً.', 'request_assigned', true);
        $this->notify($ahmed, 'الفني في الطريق', 'الفني عمر الحبسي في طريقه إليك لطلب T-2026-004. وقت الوصول المتوقع: 20 دقيقة.', 'technician_on_way', true);
        $this->notify($ahmed, 'تم إتمام طلبك بنجاح', 'تم إتمام طلب الصيانة T-2026-002. يرجى تقييم الخدمة.', 'request_completed', true);
        $this->notify($ahmed, 'تذكير: موعد قادم', 'لديك موعد صيانة غداً في Al Khuwair. الفني سيتصل بك قبل الوصول.', 'reminder', false);
        $this->notify($ahmed, 'رصيد مستحق', 'يوجد فاتورة مستحقة بقيمة 170 ريال. يرجى التسوية للتمكن من إنشاء طلبات جديدة.', 'financial_alert', false);

        // Fatima notifications
        $this->notify($fatima, 'تم تأكيد طلب التركيب', 'تم تأكيد طلب تركيب الكاسيت B-2026-011. الفني سيصل يوم '.now()->addDays(3)->format('d/m/Y').'.', 'request_confirmed', true);
        $this->notify($fatima, 'تم إتمام التركيب', 'تم إتمام تركيب وحدات LG بنجاح. شكراً لاختياركم سندباد!', 'request_completed', true);
        $this->notify($fatima, 'عرض خاص', 'خصم 10% على عقود الصيانة السنوية هذا الشهر. تواصل معنا لمزيد من التفاصيل.', 'promotion', false);

        // Mohammed notifications
        $this->notify($mohammed, 'مرحباً بك في سندباد', 'تم تسجيل طلبك T-2026-030. سنقوم بتعيين فني لك قريباً.', 'welcome', true);

        // Khalid notifications
        $this->notify($khalid, 'الفني في الطريق', 'الفني حمد السيابي في طريقه إليك لطلب التركيب B-2026-040.', 'technician_on_way', false);
        $this->notify($khalid, 'جارٍ العمل', 'بدأ الفني عمر الحبسي العمل على طلب الإصلاح T-2026-041.', 'request_in_progress', false);

        // Sara notifications
        $this->notify($sara, 'تم إلغاء الطلب', 'تم إلغاء طلبك T-2026-052. يمكنك إنشاء طلب جديد في أي وقت.', 'request_canceled', true);
        $this->notify($sara, 'تم إتمام طلبك بنجاح', 'تم إتمام طلب الصيانة T-2026-051. يرجى تقييم الخدمة.', 'request_completed', true);

        // Technician notifications
        $this->notify($tech1, 'طلب جديد بانتظارك', 'طلب صيانة جديد في Al Khuwair — T-2026-003. مجدول بعد غد.', 'new_request', false);
        $this->notify($tech2, 'تم قبول الطلب', 'تم تعيينك لطلب T-2026-041 في Al Azaiba. انطلق إلى العميل.', 'request_assigned', false);
        $this->notify($tech3, 'طلب تركيب جديد', 'طلب تركيب B-2026-040 في Al Seeb بانتظار موافقتك.', 'new_request', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Technician Locations
    // ─────────────────────────────────────────────────────────────────────────
    private function seedTechnicianLocations(array $technicians): void
    {
        [$tech1, $tech2, $tech3] = $technicians;

        // tech1 — online near Al Khuwair (assigned to Ahmed's upcoming request)
        TechnicianLocation::updateOrCreate(
            ['technician_id' => $tech1->id],
            [
                'latitude'    => 23.5912,
                'longitude'   => 58.3855,
                'heading'     => 180.0,
                'speed'       => 0.0,
                'is_online'   => true,
                'recorded_at' => now()->subMinutes(3),
                'updated_at'  => now()->subMinutes(3),
            ]
        );

        // tech2 — online, on the way to Ahmed's on_way request (near Shatti Al Qurum)
        TechnicianLocation::updateOrCreate(
            ['technician_id' => $tech2->id],
            [
                'latitude'    => 23.5850,
                'longitude'   => 58.3810,
                'heading'     => 45.0,
                'speed'       => 42.5,
                'is_online'   => true,
                'recorded_at' => now()->subMinutes(1),
                'updated_at'  => now()->subMinutes(1),
            ]
        );

        // tech3 — online, heading to Khalid's on_way installation (near Al Seeb)
        TechnicianLocation::updateOrCreate(
            ['technician_id' => $tech3->id],
            [
                'latitude'    => 23.6750,
                'longitude'   => 58.2100,
                'heading'     => 90.0,
                'speed'       => 60.0,
                'is_online'   => true,
                'recorded_at' => now()->subMinutes(2),
                'updated_at'  => now()->subMinutes(2),
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function loc(int $index): array
    {
        return $this->locations[$index];
    }

    private function makeService(
        User $customer,
        array $data,
        array $location,
        ?array $rating = null
    ): Request {
        $exists = Request::where('invoice_number', $data['invoice_number'])->first();
        if ($exists) {
            return $exists;
        }

        $request = Request::create(array_merge($data, [
            'type'      => RequestType::Service->value,
            'user_id'   => $customer->id,
            'address'   => $location['area'],
            'latitude'  => $location['lat'],
            'longitude' => $location['lng'],
            'status'    => $data['status'] instanceof RequestStatus
                ? $data['status']->value
                : $data['status'],
            'service_type' => isset($data['service_type'])
                ? ($data['service_type'] instanceof ServiceType
                    ? $data['service_type']->value
                    : $data['service_type'])
                : null,
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

        return $request;
    }

    private function makeInstallation(
        User $customer,
        array $data,
        array $location,
        ?array $rating = null
    ): Request {
        $exists = Request::where('invoice_number', $data['invoice_number'])->first();
        if ($exists) {
            return $exists;
        }

        $request = Request::create(array_merge($data, [
            'type'      => RequestType::Installation->value,
            'user_id'   => $customer->id,
            'address'   => $location['area'],
            'latitude'  => $location['lat'],
            'longitude' => $location['lng'],
            'status'    => $data['status'] instanceof RequestStatus
                ? $data['status']->value
                : $data['status'],
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

        return $request;
    }

    /** @param array<array{0:string,1:string|null,2:float,3:float,4:float,5:string,6:int}> $orders */
    private function seedManualOrders(User $customer, array $orders): void
    {
        foreach ($orders as [$inv, $qt, $total, $paid, $remaining, $status, $daysAgo]) {
            ManualOrder::firstOrCreate(
                ['invoice_number' => $inv, 'user_id' => $customer->id],
                [
                    'quotation_template' => $qt,
                    'total_amount'       => $total,
                    'paid_amount'        => $paid,
                    'remaining_amount'   => $remaining,
                    'status'             => $status,
                    'order_date'         => now()->subDays($daysAgo)->toDateString(),
                ]
            );
        }
    }

    private function notify(User $user, string $title, string $body, string $type, bool $isRead): void
    {
        // Only create if this exact notification doesn't already exist
        $exists = Notification::where('recipient_id', $user->id)
            ->where('title', $title)
            ->exists();

        if (! $exists) {
            Notification::create([
                'recipient_id' => $user->id,
                'title'        => $title,
                'body'         => $body,
                'type'         => $type,
                'read_at'      => $isRead ? now()->subHours(rand(1, 48)) : null,
            ]);
        }
    }
}
