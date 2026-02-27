<?php

namespace App\Services\Odoo;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooService implements OdooServiceInterface
{
    protected string $url;
    protected string $db;
    protected string $username;
    protected string $password;
    protected bool $sslVerify;

    public function __construct()
    {
        $this->url = config('odoo.url', '');
        $this->db = config('odoo.db', '');
        $this->username = config('odoo.username', '');
        $this->password = config('odoo.password', '');
        $this->sslVerify = (bool) config('odoo.ssl_verify', true);
    }

    protected function getUid(): int
    {
        return Cache::remember('odoo:uid', 900, function () {
            $response = $this->jsonRpc('common', 'login', [
                $this->db,
                $this->username,
                $this->password,
            ]);

            if (! is_int($response)) {
                throw new Exception('Odoo Authentication Failed: ' . json_encode($response));
            }

            return $response;
        });
    }

    protected function jsonRpc(string $service, string $method, array $args): mixed
    {
        $http = Http::withOptions(['verify' => $this->sslVerify]);

        $response = $http->post($this->url . '/jsonrpc', [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => compact('service', 'method', 'args'),
            'id' => rand(1, 100000),
        ]);

        $result = $response->json();

        if (isset($result['error'])) {
            throw new Exception('Odoo Error: ' . json_encode($result['error']));
        }

        return $result['result'] ?? null;
    }

    protected function execute(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        $uid = $this->getUid();

        return $this->jsonRpc('object', 'execute_kw', [
            $this->db,
            $uid,
            $this->password,
            $model,
            $method,
            $args,
            $kwargs,
        ]);
    }

    protected function formatPhone(string $phone): string
    {
        if (strlen($phone) === 11 && str_starts_with($phone, '968')) {
            return substr($phone, 0, 3) . ' ' . substr($phone, 3, 4) . ' ' . substr($phone, 7);
        }
        return $phone;
    }

    public function findCustomerByPhoneOrName(string $phone, ?string $name): ?array
    {
        try {
            $formatted = $this->formatPhone($phone);
            $short = strlen($phone) > 8 ? substr($phone, -8) : $phone;

            if ($name) {
                $domain = [
                    '|', '|', '|', '|', '|', '|',
                    ['name', 'ilike', '%' . $name . '%'],
                    ['phone', 'ilike', '%' . $phone . '%'],
                    ['phone', 'ilike', '%' . $formatted . '%'],
                    ['phone', 'ilike', '%' . $short . '%'],
                    ['mobile', 'ilike', '%' . $phone . '%'],
                    ['mobile', 'ilike', '%' . $formatted . '%'],
                    ['mobile', 'ilike', '%' . $short . '%'],
                ];
            } else {
                $domain = [
                    '|', '|', '|', '|', '|',
                    ['phone', 'ilike', '%' . $phone . '%'],
                    ['phone', 'ilike', '%' . $formatted . '%'],
                    ['phone', 'ilike', '%' . $short . '%'],
                    ['mobile', 'ilike', '%' . $phone . '%'],
                    ['mobile', 'ilike', '%' . $formatted . '%'],
                    ['mobile', 'ilike', '%' . $short . '%'],
                ];
            }

            $partners = $this->execute('res.partner', 'search_read', [$domain], [
                'fields' => ['id', 'name', 'phone'],
                'limit' => 1,
            ]);

            return $partners[0] ?? null;
        } catch (Exception $e) {
            Log::error('Odoo findCustomerByPhoneOrName: ' . $e->getMessage());
            return null;
        }
    }

    public function getCustomerOrders(int $odooId, ?string $phone = null, ?string $name = null): array
    {
        try {
            $orders = $this->execute('sale.order', 'search_read', [
                [['partner_id', 'child_of', $odooId]],
            ], [
                'fields' => ['id', 'name', 'date_order', 'partner_id', 'amount_due', 'amount_total', 'invoice_status', 'invoice_ids', 'sale_order_template_id'],
                'limit' => 50,
                'order' => 'date_order desc',
            ]);

            return $orders ?? [];
        } catch (Exception $e) {
            Log::error('Odoo getCustomerOrders: ' . $e->getMessage());
            return [];
        }
    }

    public function checkTaskReadiness(int $odooId): bool
    {
        try {
            $count = $this->execute('project.task', 'search_count', [
                [
                    ['partner_id', '=', $odooId],
                    ['name', '=', 'The product is complete and ready to be installed'],
                    ['state', 'in', ['done', '1_done']],
                ],
            ]);

            return ($count ?? 0) > 0;
        } catch (Exception $e) {
            Log::error('Odoo checkTaskReadiness: ' . $e->getMessage());
            return false;
        }
    }

    public function getCustomerDebt(int $odooId): float
    {
        try {
            $result = $this->execute('res.partner', 'read', [[$odooId]], ['fields' => ['debit']]);
            return (float) ($result[0]['debit'] ?? 0.0);
        } catch (Exception $e) {
            Log::error('Odoo getCustomerDebt: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getCustomerInvoices(int $odooId, ?string $phone = null): array
    {
        try {
            $domain = [
                ['partner_id', 'in', [$odooId]],
                ['move_type', '=', 'out_invoice'],
                ['state', '=', 'posted'],
                ['payment_state', '!=', 'paid'],
            ];

            $invoices = $this->execute('account.move', 'search_read', [$domain], [
                'fields' => ['name', 'amount_total', 'amount_residual', 'payment_state', 'partner_id'],
            ]);

            return $invoices ?? [];
        } catch (Exception $e) {
            Log::error('Odoo getCustomerInvoices: ' . $e->getMessage());
            return [];
        }
    }

public function getUserTasks(int $odooId): array
    {
        try {
            $tasks = $this->execute('project.task', 'search_read', [
                [['partner_id', '=', $odooId]],
            ], [
                'fields' => ['id', 'name', 'stage_id', 'date_deadline'],
                'limit' => 50,
                'order' => 'date_deadline desc, id desc',
            ]);

            if (empty($tasks)) {
                return [];
            }

            $ids = array_column($tasks, 'id');
            $states = $this->execute('project.task', 'read', [$ids], ['fields' => ['kanban_state']]);

            $stateMap = [];
            foreach ($states as $item) {
                $stateMap[$item['id']] = $item['kanban_state'];
            }

            foreach ($tasks as &$task) {
                $task['kanban_state'] = $stateMap[$task['id']] ?? 'unknown';
            }

            return $tasks;
        } catch (Exception $e) {
            Log::error('Odoo getUserTasks: ' . $e->getMessage());
            return [];
        }
    }

    protected function productsCacheVersion(): int
    {
        return (int) Cache::get('odoo:products:version', 1);
    }

    public function bustProductsCache(): void
    {
        Cache::put('odoo:products:version', $this->productsCacheVersion() + 1, 86400);
    }

    public function getProducts(int $limit = 50, int $offset = 0, string $search = ''): array
    {
        $v        = $this->productsCacheVersion();
        $cacheKey = "odoo:products:v{$v}:{$limit}:{$offset}:" . md5($search);

        try {
            return Cache::remember($cacheKey, 1800, function () use ($limit, $offset, $search) {
                $domain = [['type', 'in', ['consu', 'product']]];

                if ($search !== '') {
                    $domain[] = ['name', 'ilike', $search];
                }

                $products = $this->execute('product.template', 'search_read', [$domain], [
                    'fields' => ['id', 'name', 'list_price', 'categ_id', 'uom_id'],
                    'limit'  => $limit,
                    'offset' => $offset,
                    'order'  => 'name asc',
                ]);

                return $products ?? [];
            });
        } catch (Exception $e) {
            Log::error('Odoo getProducts: ' . $e->getMessage());
            return [];
        }
    }

    public function getProductsCount(string $search = ''): int
    {
        $v        = $this->productsCacheVersion();
        $cacheKey = "odoo:products:count:v{$v}:" . md5($search);

        try {
            return (int) Cache::remember($cacheKey, 1800, function () use ($search) {
                $domain = [['type', 'in', ['consu', 'product']]];

                if ($search !== '') {
                    $domain[] = ['name', 'ilike', $search];
                }

                return $this->execute('product.template', 'search_count', [$domain]) ?? 0;
            });
        } catch (Exception $e) {
            Log::error('Odoo getProductsCount: ' . $e->getMessage());
            return 0;
        }
    }
}
