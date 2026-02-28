<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    public static function getNavigationLabel(): string { return __('Notifications'); }

    public static function getNavigationGroup(): ?string { return __('Management'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-bell';
    }

    public static function getNavigationSort(): ?int { return 3; }

    public static function getModelLabel(): string { return __('Notification'); }
    public static function getPluralModelLabel(): string { return __('Notifications'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('recipient_id')
                ->label(__('Recipient'))
                ->relationship('recipient', 'name')
                ->searchable()
                ->nullable()
                ->helperText(__('Leave empty to broadcast to all')),
            Forms\Components\TextInput::make('title')->label(__('Title'))->required()->maxLength(500),
            Forms\Components\Textarea::make('body')->label(__('Message'))->required(),
            Forms\Components\TextInput::make('type')->label(__('Type'))->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recipient.name')
                    ->label(__('Recipient'))
                    ->default(__('Broadcast'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')->label(__('Title'))->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('type')->label(__('Type'))->default('general')->badge(),
                Tables\Columns\TextColumn::make('read_at')
                    ->label(__('Status'))
                    ->badge()
                    ->getStateUsing(fn($record) => $record->read_at ? __('Read') : __('Unread'))
                    ->color(fn($state) => $state === __('Read') ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('read_at')
                    ->nullable()
                    ->label(__('Read Status'))
                    ->trueLabel(__('Read'))
                    ->falseLabel(__('Unread')),
            ])
            ->headerActions([
                Action::make('broadcast')
                    ->label(__('Send Notification'))
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\Select::make('recipient_type')
                            ->label(__('Recipient Type'))
                            ->options([
                                'customers'   => __('All Customers'),
                                'technicians' => __('All Technicians'),
                                'custom'      => __('Custom Users'),
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('user_ids')
                            ->label(__('Select Users'))
                            ->options(
                                User::orderBy('name')
                                    ->get(['id', 'name', 'phone', 'role'])
                                    ->mapWithKeys(fn($u) => [
                                        $u->id => "{$u->name} · {$u->phone} · {$u->role}",
                                    ])
                            )
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->visible(fn(Get $get) => $get('recipient_type') === 'custom'),
                        Forms\Components\TextInput::make('title')->label(__('Title'))->required(),
                        Forms\Components\Textarea::make('body')->label(__('Message'))->required(),
                    ])
                    ->action(function (array $data) {
                        $service = app(NotificationService::class);

                        if ($data['recipient_type'] === 'custom') {
                            $users = User::whereIn('id', $data['user_ids'] ?? [])->get();
                            $users->each(fn($user) => $service->notifyUser($user, $data['title'], $data['body'], ['type' => 'custom']));
                            FilamentNotification::make()
                                ->title(__('Notification sent to :count users', ['count' => $users->count()]))
                                ->success()->send();
                            return;
                        }

                        match ($data['recipient_type']) {
                            'customers'   => $service->notifyRole('customer', $data['title'], $data['body'], ['type' => 'broadcast']),
                            'technicians' => $service->notifyRole('technician', $data['title'], $data['body'], ['type' => 'broadcast']),
                        };
                        FilamentNotification::make()->title(__('Notification sent'))->success()->send();
                    }),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('No notifications yet'))
            ->emptyStateIcon('heroicon-o-bell');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
