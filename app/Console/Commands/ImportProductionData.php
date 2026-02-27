<?php

namespace App\Console\Commands;

use App\Models\Rating;
use App\Models\Request;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ImportProductionData extends Command
{
    protected $signature = 'import:production
                            {--token= : Bearer token from a production admin login}
                            {--url=https://back.sindbad.om/public/api : Production API base URL}
                            {--with-details : Fetch each request individually to import complete data (ratings, timestamps)}
                            {--dry-run : Preview what would be imported without writing to the database}';

    protected $description = 'Import users and requests from the production API into this sindbad-v2 database';

    private string $baseUrl;
    private string $token;

    /** @var array<int, int> old user ID → new user ID */
    private array $userMap = [];

    /** @var array<int, int> old service request ID → new request ID */
    private array $serviceRequestMap = [];

    /** @var array<int, int> old installation request ID → new request ID */
    private array $installationRequestMap = [];

    public function handle(): int
    {
        $this->token   = $this->option('token') ?? '';
        $this->baseUrl = rtrim($this->option('url'), '/');

        if (! $this->token) {
            $this->error('--token is required. Log in to the production admin panel and copy your Bearer token from DevTools → Application → Local Storage → "token".');
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] No data will be written.');
        }

        $this->info("Production API: {$this->baseUrl}");
        $this->newLine();

        // Verify the token works
        $check = $this->apiGet('/profile');
        if (! $check || ! isset($check['phone'])) {
            $this->error('Token is invalid or expired. Aborting.');
            return self::FAILURE;
        }
        $this->info("Authenticated as: {$check['name']} ({$check['phone']})");
        if ($check['role'] !== 'admin') {
            $this->error('Token belongs to a non-admin user. Admin token required. Aborting.');
            return self::FAILURE;
        }

        $this->newLine();

        $this->importUsers();
        $this->importServiceRequests();
        $this->importInstallationRequests();

        $this->newLine();
        $this->info('✔ Import complete.');
        $this->newLine();

        $this->table(
            ['Table', 'Rows in v2'],
            [
                ['users (non-admin)', User::where('role', '!=', 'admin')->count()],
                ['requests (service)', Request::where('type', 'service')->count()],
                ['requests (installation)', Request::where('type', 'installation')->count()],
                ['ratings', Rating::count()],
            ]
        );

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function importUsers(): void
    {
        $this->info('1/3  Fetching users from /admin/users …');

        $response = $this->apiGet('/admin/users');

        if (! $response || empty($response['data'])) {
            $this->warn('     No users returned. Skipping.');
            return;
        }

        $users = collect($response['data'])->filter(fn ($u) => ($u['role'] ?? '') !== 'admin');
        $count = $users->count();
        $this->line("     Found {$count} non-admin users.");

        if ($this->option('dry-run')) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($users as $u) {
            // Upsert by phone so re-running the command is idempotent
            $user = User::updateOrCreate(
                ['phone' => $u['phone']],
                [
                    'name'               => $u['name'],
                    'email'              => $u['email'] ?? null,
                    'role'               => $u['role'],
                    'is_active'          => (bool) ($u['is_active'] ?? false),
                    'odoo_id'            => $u['odoo_id'] ?? null,
                    'fcm_token'          => $u['fcm_token'] ?? null,
                    'invoice_number'     => $u['invoice_number'] ?? null,
                    'quotation_template' => $u['quotation_template'] ?? null,
                    'profile_link'       => $u['profile_link'] ?? null,
                    // Keep existing password if user already in v2 DB, else set a placeholder
                    'password'           => Hash::make('sindbad2026'),
                ]
            );

            $this->userMap[(int) $u['id']] = $user->id;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function importServiceRequests(): void
    {
        $this->info('2/3  Fetching service requests from /requests …');

        $response = $this->apiGet('/requests');

        if (! $response || empty($response['data'])) {
            $this->warn('     No service requests returned. Skipping.');
            return;
        }

        $items = $response['data'];
        $count = count($items);
        $this->line("     Found {$count} service requests.");

        if ($this->option('dry-run')) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($items as $item) {
            $detail = $item;

            if ($this->option('with-details')) {
                $detailResponse = $this->apiGet("/admin/requests/{$item['id']}");
                if ($detailResponse && isset($detailResponse['data'])) {
                    $detail = $detailResponse['data'];
                }
            }

            $newId = $this->upsertRequest($detail, 'service');
            $this->serviceRequestMap[(int) $item['id']] = $newId;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function importInstallationRequests(): void
    {
        $this->info('3/3  Fetching installation requests from /installation-requests …');

        $response = $this->apiGet('/installation-requests');

        if (! $response || empty($response['data'])) {
            $this->warn('     No installation requests returned. Skipping.');
            return;
        }

        $items = $response['data'];
        $count = count($items);
        $this->line("     Found {$count} installation requests.");

        if ($this->option('dry-run')) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($items as $item) {
            $detail = $item;

            if ($this->option('with-details')) {
                $detailResponse = $this->apiGet("/admin/installation-requests/{$item['id']}");
                if ($detailResponse && isset($detailResponse['data'])) {
                    $detail = $detailResponse['data'];
                }
            }

            $newId = $this->upsertRequest($detail, 'installation');
            $this->installationRequestMap[(int) $item['id']] = $newId;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Insert or update a request record.
     * Returns the new request ID.
     */
    private function upsertRequest(array $item, string $type): int
    {
        // Resolve user IDs via the map (fall back to the raw ID if user was skipped/already in v2)
        $oldUserId      = $item['user']['id']       ?? $item['user_id']       ?? null;
        $oldTechId      = $item['technician']['id'] ?? $item['technician_id'] ?? null;
        $newUserId      = $this->userMap[(int) $oldUserId]  ?? (int) $oldUserId  ?: null;
        $newTechId      = $oldTechId ? ($this->userMap[(int) $oldTechId] ?? (int) $oldTechId) : null;

        $scheduledAt = $this->normalizeDate($item['scheduled_at'] ?? null);
        $endDate     = $this->normalizeDate($item['end_date']     ?? null);

        // Use invoice_number exactly as stored in production (already has T-/B- prefix)
        $invoiceNumber = $item['invoice_number'] ?? null;

        $data = [
            'type'                    => $type,
            'user_id'                 => $newUserId,
            'technician_id'           => $newTechId,
            'status'                  => $item['status'] ?? 'pending',
            'invoice_number'          => $invoiceNumber,
            'address'                 => $item['address'] ?? null,
            'latitude'                => $item['latitude'] ?? null,
            'longitude'               => $item['longitude'] ?? null,
            'scheduled_at'            => $scheduledAt,
            'end_date'                => $endDate,
            'completed_at'            => $item['completed_at'] ?? null,
            'technician_accepted_at'  => $item['technician_accepted_at'] ?? null,
            'task_start_time'         => $item['task_start_time'] ?? null,
            'task_end_time'           => $item['task_end_time'] ?? null,
            // Service-only
            'service_type'            => $item['service_type'] ?? null,
            'description'             => $item['description'] ?? null,
            // Installation-only
            'product_type'            => $item['product_type'] ?? null,
            'quantity'                => (int) ($item['quantity'] ?? 1),
            'is_site_ready'           => (bool) ($item['is_site_ready'] ?? false),
            'notes'                   => $item['notes'] ?? null,
            'readiness_details'       => isset($item['readiness_details'])
                ? json_encode($item['readiness_details'])
                : null,
            'created_at'              => $item['created_at'] ?? now(),
            'updated_at'              => $item['updated_at'] ?? now(),
        ];

        // Upsert by (type, invoice_number) so re-running is idempotent.
        // Falls back to create-only if invoice_number is null.
        if ($invoiceNumber) {
            $existing = DB::table('requests')
                ->where('type', $type)
                ->where('invoice_number', $invoiceNumber)
                ->first();

            if ($existing) {
                DB::table('requests')->where('id', $existing->id)->update($data);
                $newRequestId = $existing->id;
            } else {
                $newRequestId = DB::table('requests')->insertGetId($data);
            }
        } else {
            $newRequestId = DB::table('requests')->insertGetId($data);
        }

        // Import embedded rating (only available when --with-details fetches the show endpoint)
        if (! empty($item['rating']) && is_array($item['rating'])) {
            $this->upsertRating($newRequestId, $item['rating'], $newUserId);
        }

        return $newRequestId;
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function upsertRating(int $requestId, array $rating, ?int $userId): void
    {
        DB::table('ratings')->updateOrInsert(
            ['request_id' => $requestId],
            [
                'request_id'     => $requestId,
                'user_id'        => $rating['user_id'] ?? $userId,
                'product_rating' => $rating['product_rating'] ?? null,
                'service_rating' => $rating['service_rating'] ?? null,
                'how_found_us'   => $rating['how_found_us'] ?? null,
                // Old service requests stored rating as plain "review_comment"; new unified table uses customer_notes
                'customer_notes' => $rating['customer_notes'] ?? $rating['review_comment'] ?? null,
                'created_at'     => $rating['created_at'] ?? now(),
                'updated_at'     => $rating['updated_at'] ?? now(),
            ]
        );
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function apiGet(string $path): ?array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->acceptJson()
                ->get("{$this->baseUrl}{$path}");

            if ($response->failed()) {
                $this->warn("  HTTP {$response->status()} on GET {$path}");
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            $this->warn("  Request failed for {$path}: {$e->getMessage()}");
            return null;
        }
    }

    private function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        // Strip time portion — scheduled_at and end_date are stored as DATE in v2
        return substr($value, 0, 10);
    }
}
