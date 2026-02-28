<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Pages\CustomerDetailPage;
use App\Filament\Pages\TechnicianDetailPage;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationLabel(): string { return __('Users'); }

    public static function getNavigationGroup(): ?string { return __('Management'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationSort(): ?int { return 4; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('manualOrders');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('Basic Information'))->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label(__('Phone'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(30)
                    ->disabled(fn(string $operation) => $operation === 'edit')
                    ->helperText(fn(string $operation) => $operation === 'edit' ? __('Phone number cannot be changed — it is the login identifier.') : null),
                Forms\Components\Select::make('role')
                    ->label(__('Role'))
                    ->options(collect(UserRole::cases())->mapWithKeys(fn($r) => [$r->value => $r->label()]))
                    ->required()
                    ->live()
                    ->disabled(fn(string $operation) => $operation === 'edit')
                    ->helperText(fn(string $operation) => $operation === 'edit' ? __('Role cannot be changed after account creation.') : null),
                Forms\Components\TextInput::make('odoo_id')
                    ->numeric()
                    ->nullable()
                    ->label(__('Odoo ID'))
                    ->hidden(fn(string $operation, Get $get) => $operation === 'edit' && $get('role') === UserRole::Admin->value)
                    ->disabled(fn(string $operation, Get $get) => $operation === 'edit' && $get('role') === UserRole::Customer->value)
                    ->helperText(fn(string $operation, Get $get) => $operation === 'edit' && $get('role') === UserRole::Customer->value ? __('Odoo ID cannot be changed after account creation.') : null),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->nullable()
                    ->minLength(6)
                    ->dehydrateStateUsing(fn($s) => filled($s) ? bcrypt($s) : null)
                    ->dehydrated(fn($s) => filled($s))
                    ->label(__('New Password'))
                    ->helperText(__('Leave blank to keep the current password.'))
                    ->hidden(fn(Get $get) => $get('role') === UserRole::Customer->value),
                Forms\Components\TextInput::make('password_confirmation')
                    ->password()
                    ->nullable()
                    ->requiredWith('password')
                    ->rules(['same:password'])
                    ->label(__('Confirm New Password'))
                    ->dehydrated(false)
                    ->hidden(fn(Get $get) => $get('role') === UserRole::Customer->value),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(false)
                    ->disabled(fn(string $operation, ?User $record) => $operation === 'edit' && $record?->id === auth()->id())
                    ->helperText(fn(string $operation, ?User $record) => $operation === 'edit' && $record?->id === auth()->id() ? __('You cannot change the active status of your own account.') : null),
            ])->columns(2),

            Section::make(__('Manual Orders'))
                ->schema([
                    Forms\Components\Repeater::make('manualOrders')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('invoice_number')->label(__('Invoice Number'))->required(),
                            Forms\Components\TextInput::make('quotation_template')->label(__('Quotation Template')),
                            Forms\Components\TextInput::make('total_amount')->label(__('Total Amount'))->numeric()->required()->default(0),
                            Forms\Components\TextInput::make('paid_amount')->label(__('Paid Amount'))->numeric()->default(0),
                            Forms\Components\TextInput::make('remaining_amount')->label(__('Remaining Amount'))->numeric()->default(0),
                            Forms\Components\Select::make('status')
                                ->label(__('Status'))
                                ->options(['paid' => __('Paid'), 'partial' => __('Partial')])
                                ->default('partial')
                                ->required(),
                            Forms\Components\DatePicker::make('order_date')->label(__('Order Date'))->default(now()),
                        ])
                        ->columns(3)
                        ->collapsible()
                        ->addActionLabel(__('Add Invoice'))
                        ->defaultItems(0),
                ])
                ->visible(fn(Get $get) => $get('role') === UserRole::Customer->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label(__('Avatar'))
                    ->circular()
                    ->defaultImageUrl(fn(User $record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&color=6366f1&background=e0e7ff&size=64')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable(),
                Tables\Columns\TextColumn::make('phone')->label(__('Phone'))->searchable()->copyable(),
                Tables\Columns\TextColumn::make('role')->label(__('Role'))
                    ->badge()
                    ->color(fn($state) => match($state) {
                        UserRole::Admin->value      => 'danger',
                        UserRole::Technician->value => 'primary',
                        UserRole::Customer->value   => 'success',
                        default                     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('manual_orders_count')
                    ->label(__('Invoices'))
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('odoo_id')
                    ->label(__('Odoo ID'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('default_address')
                    ->label(__('Address'))
                    ->limit(35)
                    ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->label(__('Active'))->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('Role'))
                    ->options(collect(UserRole::cases())->mapWithKeys(fn($r) => [$r->value => $r->label()]))
                    ->placeholder(__('All Roles')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only')),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordUrl(fn(User $record) => match($record->role) {
                UserRole::Customer->value   => CustomerDetailPage::getUrl(['id' => $record->id]),
                UserRole::Technician->value => TechnicianDetailPage::getUrl(['id' => $record->id]),
                default                     => static::getUrl('edit', ['record' => $record->id]),
            })
            ->actions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn(User $record) => $record->is_active ? __('Deactivate') : __('Activate'))
                    ->icon(fn(User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(User $record) => $record->is_active ? 'danger' : 'warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn(User $record) => ($record->is_active ? __('Deactivate') : __('Activate')) . ' ' . $record->name)
                    ->modalDescription(fn(User $record) => $record->is_active
                        ? __('This user will no longer be able to log in.')
                        : __('This user will be able to log in again.'))
                    ->disabled(fn(User $record) => $record->id === auth()->id())
                    ->action(function (User $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? __('User activated') : __('User deactivated'))
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
                    ->action(function (User $record, array $data) {
                        app(NotificationService::class)->notifyUser($record, $data['title'], $data['body'], ['type' => 'custom']);
                        Notification::make()->title(__('Notification sent'))->success()->send();
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading(__('No users yet'))
            ->emptyStateIcon('heroicon-o-users')
            ->paginationPageOptions([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit'  => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
