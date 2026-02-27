<?php

namespace App\Filament\Resources\InstallationRequestResource\Pages;

use App\Enums\RequestStatus;
use App\Filament\Resources\InstallationRequestResource;
use App\Models\User;
use App\Services\RequestService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;

class ViewInstallationRequest extends ViewRecord
{
    protected static string $resource = InstallationRequestResource::class;

    protected string $view = 'filament.resources.installation-request-resource.view-installation-request';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign')
                ->label(__('Assign Technician'))
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn() => !in_array($this->getRecord()->status, [RequestStatus::Completed, RequestStatus::Canceled]))
                ->fillForm(fn() => [
                    'customer_preferred_date' => $this->getRecord()->scheduled_at?->format('M d, Y') ?? __('Not set'),
                    'scheduled_at' => $this->getRecord()->scheduled_at,
                ])
                ->form([
                    Forms\Components\TextInput::make('customer_preferred_date')
                        ->label(__("Customer's Preferred Date"))
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('technician_id')
                        ->label(__('Technician'))
                        ->options(User::technicians()->active()->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('scheduled_at')->label(__('Start Date')),
                        Forms\Components\DatePicker::make('end_date')->label(__('End Date')),
                    ]),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    app(RequestService::class)->assignTechnician($record, $data['technician_id'], $data);
                    Notification::make()->title(__('Technician assigned'))->success()->send();
                    $this->refreshRecord();
                }),
            Action::make('updateStatus')
                ->label(__('Change Status'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->modalWidth('sm')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label(__('New Status'))
                        ->options(collect(RequestStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                        ->default(fn() => $this->getRecord()->status->value ?? $this->getRecord()->status)
                        ->required(),
                    Forms\Components\Toggle::make('notify_client')
                        ->label(__('Notify client'))
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $newStatus = RequestStatus::from($data['status']);

                    if ($data['notify_client']) {
                        app(RequestService::class)->updateStatus($record, $newStatus, auth()->user());
                    } else {
                        $updateData = ['status' => $newStatus->value];
                        if ($newStatus === RequestStatus::Completed) {
                            $updateData['completed_at'] = now();
                        }
                        $record->update($updateData);
                    }

                    Notification::make()->title(__('Status updated to :status', ['status' => $newStatus->label()]))->success()->send();
                    $this->refreshRecord();
                }),
            EditAction::make(),
            DeleteAction::make()->successRedirectUrl(InstallationRequestResource::getUrl()),
        ];
    }

    protected function getViewData(): array
    {
        $record = $this->getRecord()->load(['user', 'technician', 'rating', 'activities.causer']);
        return [
            'record'           => $record,
            'customerImages'   => $record->getMedia('attachments'),
            'technicianImages' => $record->getMedia('technician_images'),
        ];
    }
}
