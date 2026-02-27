<?php

namespace App\Filament\Pages;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Rating;
use App\Models\Request;
use App\Models\User;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;

class CustomerDetailPage extends Page
{
    protected string $view = 'filament.pages.customer-detail';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // ── Record ──────────────────────────────────────────────────────────────
    public User $customer;

    // ── Tabs ────────────────────────────────────────────────────────────────
    public string $activeTab = 'overview';

    // ── Stats ───────────────────────────────────────────────────────────────
    public array $stats = [];

    // ── Service Requests ────────────────────────────────────────────────────
    public array  $serviceRequests     = [];
    public int    $serviceTotal        = 0;
    public int    $servicePage         = 1;
    public int    $perPage             = 10;

    // ── Installation Requests ───────────────────────────────────────────────
    public array  $installationRequests = [];
    public int    $installationTotal    = 0;
    public int    $installationPage     = 1;

    // ── Manual Orders ───────────────────────────────────────────────────────
    public array $manualOrders         = [];
    public float $totalSpent           = 0;
    public float $totalPaid            = 0;
    public float $totalRemaining       = 0;

    // ── Ratings ─────────────────────────────────────────────────────────────
    public array $ratings              = [];

    // ── Odoo Orders ─────────────────────────────────────────────────────────
    public array $odooOrders           = [];
    public bool  $odooOrdersFetched    = false;


    // ────────────────────────────────────────────────────────────────────────

    public function mount(int $id): void
    {
        $this->customer = User::where('role', UserRole::Customer->value)->findOrFail($id);
        $this->loadAll();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->customer->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleActive')
                ->label(fn() => $this->customer->is_active ? __('Deactivate') : __('Activate'))
                ->icon(fn() => $this->customer->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn() => $this->customer->is_active ? 'danger' : 'warning')
                ->requiresConfirmation()
                ->modalHeading(fn() => ($this->customer->is_active ? __('Deactivate') : __('Activate')) . ' ' . $this->customer->name)
                ->modalDescription(fn() => $this->customer->is_active
                    ? __('This customer will no longer be able to log in.')
                    : __('This customer will be able to log in again.'))
                ->action(function () {
                    $this->customer->update(['is_active' => ! $this->customer->is_active]);
                    $this->customer->refresh();
                    Notification::make()
                        ->title($this->customer->is_active ? __('Customer activated') : __('Customer deactivated'))
                        ->success()
                        ->send();
                }),
            Action::make('notify')
                ->label(__('Notify'))
                ->icon('heroicon-o-bell')
                ->form([
                    Forms\Components\TextInput::make('title')->label(__('Title'))->required(),
                    Forms\Components\Textarea::make('body')->label(__('Message'))->required(),
                ])
                ->action(function (array $data) {
                    app(NotificationService::class)->notifyUser(
                        $this->customer,
                        $data['title'],
                        $data['body'],
                        ['type' => 'custom']
                    );
                    Notification::make()->title(__('Notification sent'))->success()->send();
                }),
            Action::make('edit')
                ->label(__('Edit'))
                ->icon('heroicon-o-pencil')
                ->url(fn() => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $this->customer->id])),
            Action::make('delete')
                ->label(__('Delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('Delete Customer?'))
                ->modalDescription(fn() => __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $this->customer->name]))
                ->action(function () {
                    $this->customer->delete();
                    $this->redirect(\App\Filament\Resources\UserResource::getUrl('index'));
                }),
        ];
    }

    // ── Data Loaders ────────────────────────────────────────────────────────

    private function loadAll(): void
    {
        $this->loadStats();
        $this->loadServiceRequests();
        $this->loadInstallationRequests();
        $this->loadManualOrders();
        $this->loadRatings();
    }

    private function loadStats(): void
    {
        $id = $this->customer->id;

        $totalReqs   = Request::where('user_id', $id)->count();
        $completedReqs = Request::where('user_id', $id)->where('status', RequestStatus::Completed->value)->count();

        $avgRating = Rating::whereHas('request', fn($q) => $q->where('user_id', $id))
            ->selectRaw('AVG((COALESCE(product_rating,0) + COALESCE(service_rating,0)) / 2.0) as avg')
            ->value('avg');

        $this->stats = [
            'total_requests' => $totalReqs,
            'completed'      => $completedReqs,
            'avg_rating'     => $avgRating ? round((float) $avgRating, 1) : null,
            'total_spent'    => (float) $this->customer->manualOrders()->sum('total_amount'),
            'outstanding'    => (float) $this->customer->manualOrders()->sum('remaining_amount'),
        ];
    }

    private function loadServiceRequests(): void
    {
        $query = Request::with(['technician:id,name', 'rating'])
            ->where('user_id', $this->customer->id)
            ->where('type', 'service')
            ->orderByDesc('created_at');

        $this->serviceTotal = $query->count();
        $this->serviceRequests = $query
            ->skip(($this->servicePage - 1) * $this->perPage)
            ->take($this->perPage)
            ->get()
            ->map(fn($r) => $this->formatRequest($r))
            ->toArray();
    }

    private function loadInstallationRequests(): void
    {
        $query = Request::with(['technician:id,name', 'rating'])
            ->where('user_id', $this->customer->id)
            ->where('type', 'installation')
            ->orderByDesc('created_at');

        $this->installationTotal = $query->count();
        $this->installationRequests = $query
            ->skip(($this->installationPage - 1) * $this->perPage)
            ->take($this->perPage)
            ->get()
            ->map(fn($r) => $this->formatRequest($r))
            ->toArray();
    }

    private function loadManualOrders(): void
    {
        $orders = $this->customer->manualOrders()->orderByDesc('order_date')->get();
        $this->manualOrders    = $orders->map(fn($o) => [
            'id'                 => $o->id,
            'invoice_number'     => $o->invoice_number,
            'quotation_template' => $o->quotation_template,
            'total_amount'       => (float) $o->total_amount,
            'paid_amount'        => (float) $o->paid_amount,
            'remaining_amount'   => (float) $o->remaining_amount,
            'status'             => $o->status instanceof \App\Enums\ManualOrderStatus ? $o->status->value : $o->status,
            'order_date'         => $o->order_date?->format('Y-m-d'),
        ])->toArray();

        $this->totalSpent     = (float) $orders->sum('total_amount');
        $this->totalPaid      = (float) $orders->sum('paid_amount');
        $this->totalRemaining = (float) $orders->sum('remaining_amount');
    }

    private function loadRatings(): void
    {
        $this->ratings = Rating::whereHas('request', fn($q) => $q->where('user_id', $this->customer->id))
            ->with('request:id,invoice_number,type,completed_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($rating) => [
                'invoice_number' => $rating->request?->invoice_number,
                'type'           => $rating->request?->type?->value,
                'product_rating' => $rating->product_rating,
                'service_rating' => $rating->service_rating,
                'notes'          => $rating->customer_notes,
                'how_found_us'   => $rating->how_found_us,
                'completed_at'   => $rating->request?->completed_at?->format('Y-m-d'),
            ])
            ->toArray();
    }

    public function loadOdooOrders(): void
    {
        if ($this->odooOrdersFetched) {
            return;
        }

        if (! $this->customer->odoo_id) {
            $this->odooOrdersFetched = true;
            return;
        }

        try {
            $raw = app(\App\Services\Odoo\OdooServiceInterface::class)
                ->getCustomerOrders((int) $this->customer->odoo_id);

            $this->odooOrders = array_map(fn($o) => [
                'reference'      => $o['name'],
                'date'           => $o['date_order'] ? substr($o['date_order'], 0, 10) : null,
                'template'       => is_array($o['sale_order_template_id']) ? $o['sale_order_template_id'][1] : null,
                'total'          => (float) ($o['amount_total'] ?? 0),
                'amount_due'     => (float) ($o['amount_due'] ?? 0),
                'invoice_status' => $o['invoice_status'] ?? 'nothing',
            ], $raw);
        } catch (\Exception $e) {
            $this->odooOrders = [];
        }

        $this->odooOrdersFetched = true;
    }

    private function formatRequest(Request $r): array
    {
        return [
            'id'             => $r->id,
            'invoice_number' => $r->invoice_number,
            'type'           => $r->type->value,
            'service_type'   => $r->service_type?->value,
            'product_type'   => $r->product_type,
            'quantity'       => $r->quantity,
            'status'         => $r->status->value,
            'status_label'   => $r->status->label(),
            'status_color'   => $r->status->color(),
            'address'        => $r->address,
            'technician'     => $r->technician?->name ?? __('Unassigned'),
            'scheduled_at'   => $r->scheduled_at?->format('Y-m-d'),
            'completed_at'   => $r->completed_at?->format('Y-m-d H:i'),
            'rating'         => $r->rating
                ? round(((float) ($r->rating->product_rating ?? 0) + (float) ($r->rating->service_rating ?? 0)) / 2, 1)
                : null,
        ];
    }

    // ── Tab Actions ─────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ── Service Request Pagination ───────────────────────────────────────────

    public function serviceNextPage(): void
    {
        if ($this->servicePage < ceil($this->serviceTotal / $this->perPage)) {
            $this->servicePage++;
            $this->loadServiceRequests();
        }
    }

    public function servicePrevPage(): void
    {
        if ($this->servicePage > 1) {
            $this->servicePage--;
            $this->loadServiceRequests();
        }
    }

    // ── Installation Request Pagination ─────────────────────────────────────

    public function installationNextPage(): void
    {
        if ($this->installationPage < ceil($this->installationTotal / $this->perPage)) {
            $this->installationPage++;
            $this->loadInstallationRequests();
        }
    }

    public function installationPrevPage(): void
    {
        if ($this->installationPage > 1) {
            $this->installationPage--;
            $this->loadInstallationRequests();
        }
    }

    // ── Routing ─────────────────────────────────────────────────────────────

    public static function routes(Panel $panel): void
    {
        Route::get('/customers/{id}', static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getRelativeRouteName($panel));
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'customers';
    }
}
