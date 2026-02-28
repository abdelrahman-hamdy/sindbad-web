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
use Filament\Panel;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;

class TechnicianDetailPage extends Page
{
    protected string $view = 'filament.pages.technician-detail';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public User $technician;
    public array $techStats = [];
    public string $filter = 'all';
    public int $page = 1;
    public array $requests = [];
    public int $totalRequests = 0;
    public int $perPage = 15;

    public function mount(int $id): void
    {
        $this->technician = User::where('role', UserRole::Technician->value)->findOrFail($id);
        $this->computeStats();
        $this->loadRequests();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->technician->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleActive')
                ->label(fn() => $this->technician->is_active ? __('Deactivate') : __('Activate'))
                ->icon(fn() => $this->technician->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn() => $this->technician->is_active ? 'danger' : 'warning')
                ->requiresConfirmation()
                ->modalHeading(fn() => ($this->technician->is_active ? __('Deactivate') : __('Activate')) . ' ' . $this->technician->name)
                ->modalDescription(fn() => $this->technician->is_active
                    ? __('This technician will no longer be able to log in or receive requests.')
                    : __('This technician will be able to log in and receive requests again.'))
                ->action(function () {
                    $this->technician->update(['is_active' => ! $this->technician->is_active]);
                    $this->technician->refresh();
                    Notification::make()
                        ->title($this->technician->is_active ? __('Technician activated') : __('Technician deactivated'))
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
                        $this->technician,
                        $data['title'],
                        $data['body'],
                        ['type' => 'custom']
                    );
                    Notification::make()->title(__('Notification sent'))->success()->send();
                }),
            Action::make('edit')
                ->label(__('Edit'))
                ->icon('heroicon-o-pencil')
                ->url(fn() => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $this->technician->id])),
            Action::make('delete')
                ->label(__('Delete'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('Delete Technician?'))
                ->modalDescription(fn() => __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $this->technician->name]))
                ->action(function () {
                    $this->technician->delete();
                    $this->redirect(\App\Filament\Resources\UserResource::getUrl('index'));
                }),
        ];
    }

    private function computeStats(): void
    {
        $total = Request::where('technician_id', $this->technician->id)->count();
        $completed = Request::where('technician_id', $this->technician->id)
            ->where('status', RequestStatus::Completed->value)->count();
        $accepted = Request::where('technician_id', $this->technician->id)
            ->whereNotNull('technician_accepted_at')->count();

        $avgRating = Rating::whereHas('request', fn($q) => $q->where('technician_id', $this->technician->id))
            ->selectRaw('AVG((COALESCE(product_rating,0) + COALESCE(service_rating,0)) / 2) as avg')
            ->value('avg');

        $this->techStats = [
            'total'           => $total,
            'completed'       => $completed,
            'avg_rating'      => $avgRating ? round((float)$avgRating, 1) : null,
            'acceptance_rate' => $total > 0 ? round($accepted / $total * 100, 1) : 0,
        ];
    }

    public function updatedFilter(): void
    {
        $this->page = 1;
        $this->loadRequests();
    }

    public function loadRequests(): void
    {
        $query = Request::with(['user:id,name,phone', 'rating'])
            ->where('technician_id', $this->technician->id);

        if ($this->filter === 'service') {
            $query->where('type', 'service');
        } elseif ($this->filter === 'installation') {
            $query->where('type', 'installation');
        }

        $this->totalRequests = $query->count();
        $this->requests = $query
            ->orderByDesc('created_at')
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get()
            ->map(fn($req) => [
                'id'             => $req->id,
                'invoice_number' => $req->invoice_number,
                'type'           => $req->type->value,
                'customer'       => $req->user?->name ?? '-',
                'status'         => $req->status->value,
                'status_label'   => $req->status->label(),
                'scheduled_at'   => $req->scheduled_at?->format('Y-m-d'),
                'completed_at'   => $req->completed_at?->format('Y-m-d H:i'),
                'rating'         => $req->rating
                    ? round(((float)($req->rating->product_rating ?? 0) + (float)($req->rating->service_rating ?? 0)) / 2, 1)
                    : null,
            ])
            ->toArray();
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadRequests();
        }
    }

    public function nextPage(): void
    {
        if ($this->page < ceil($this->totalRequests / $this->perPage)) {
            $this->page++;
            $this->loadRequests();
        }
    }

    public static function routes(Panel $panel): void
    {
        Route::get('/technicians/{id}', static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getRelativeRouteName($panel));
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'technicians';
    }
}
