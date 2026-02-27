<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createCustomer')
                ->label(__('Add Customer'))
                ->icon('heroicon-o-user')
                ->color('success')
                ->modalWidth('4xl')
                ->form([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone'))
                            ->required()
                            ->tel()
                            ->maxLength(30)
                            ->unique('users', 'phone')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (blank($state)) {
                                    return;
                                }
                                $partner = app(\App\Services\Odoo\OdooServiceInterface::class)
                                    ->findCustomerByPhoneOrName($state, null);
                                $set('name', $partner['name'] ?? null);
                                $set('odoo_id', $partner ? $partner['id'] : null);
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('Auto-filled from Odoo after entering phone')),
                        Forms\Components\TextInput::make('odoo_id')
                            ->numeric()
                            ->nullable()
                            ->label(__('Odoo ID'))
                            ->readOnly()
                            ->placeholder(__('Auto-fetched from Odoo')),
                        Forms\Components\Placeholder::make('odoo_lookup_status')
                            ->label('')
                            ->columnSpanFull()
                            ->content(function (callable $get) {
                                $phone = $get('phone');
                                $odooId = $get('odoo_id');

                                if (! filled($phone)) {
                                    $resultHtml = '<span class="text-sm text-gray-400 italic">' . __('Enter the phone number — name and Odoo ID will be filled automatically.') . '</span>';
                                } elseif (filled($odooId)) {
                                    $resultHtml = '<span class="inline-flex items-center gap-1.5 text-sm font-medium text-success-600 dark:text-success-400">'
                                        . '<svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>'
                                        . __('Found in Odoo — ID: :id. Name is editable.', ['id' => e($odooId)])
                                        . '</span>';
                                } else {
                                    $resultHtml = '<span class="inline-flex items-center gap-1.5 text-sm font-medium text-warning-600 dark:text-warning-400">'
                                        . '<svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>'
                                        . __('Not found in Odoo — enter the name manually.')
                                        . '</span>';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<div>'
                                    . '<span wire:loading class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">'
                                    . '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">'
                                    . '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>'
                                    . '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>'
                                    . '</svg>'
                                    . __('Searching in Odoo...')
                                    . '</span>'
                                    . '<span wire:loading.remove>' . $resultHtml . '</span>'
                                    . '</div>'
                                );
                            }),
                    ]),
                    Forms\Components\Repeater::make('orders')
                        ->label(__('Manual Orders'))
                        ->schema([
                            Forms\Components\TextInput::make('invoice_number')->label(__('Invoice Number'))->required(),
                            Forms\Components\TextInput::make('quotation_template')->label(__('Quotation Template')),
                            Forms\Components\TextInput::make('total_amount')->label(__('Total Amount'))->numeric()->required()->default(0),
                            Forms\Components\TextInput::make('paid_amount')->label(__('Paid Amount'))->numeric()->default(0)->required(),
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
                ->action(function (array $data) {
                    $user = User::create([
                        'name'      => $data['name'],
                        'phone'     => $data['phone'],
                        'odoo_id'   => $data['odoo_id'] ?? null,
                        'is_active' => false,
                        'role'      => 'customer',
                        'password'  => Hash::make(Str::random(40)),
                    ]);
                    $user->assignRole('customer');

                    foreach ($data['orders'] ?? [] as $order) {
                        $user->manualOrders()->create($order);
                    }

                    Notification::make()->title(__('Customer created'))->success()->send();
                }),

            Action::make('createTechnician')
                ->label(__('Add Technician'))
                ->icon('heroicon-o-wrench')
                ->color('primary')
                ->form([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone')->label(__('Phone'))->required()->tel()->maxLength(30)->unique('users', 'phone'),
                        Forms\Components\Toggle::make('is_active')->label(__('Active'))->default(true)->columnSpanFull(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->nullable()
                            ->minLength(6)
                            ->label(__('Password'))
                            ->helperText(__('Leave blank to auto-generate a secure password.')),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->nullable()
                            ->requiredWith('password')
                            ->rules(['same:password'])
                            ->label(__('Confirm Password')),
                    ]),
                ])
                ->action(function (array $data) {
                    $user = User::create([
                        'name'      => $data['name'],
                        'phone'     => $data['phone'],
                        'is_active' => $data['is_active'] ?? true,
                        'role'      => 'technician',
                        'password'  => Hash::make($data['password'] ?: Str::random(16)),
                    ]);
                    $user->assignRole('technician');

                    Notification::make()->title(__('Technician created'))->success()->send();
                }),

            Action::make('createAdmin')
                ->label(__('Add Admin'))
                ->icon('heroicon-o-shield-check')
                ->color('danger')
                ->form([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
                        Forms\Components\TextInput::make('phone')->label(__('Phone'))->required()->tel()->maxLength(30)->unique('users', 'phone'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->nullable()
                            ->minLength(8)
                            ->label(__('Password'))
                            ->helperText(__('Leave blank to auto-generate a secure password.')),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->nullable()
                            ->requiredWith('password')
                            ->rules(['same:password'])
                            ->label(__('Confirm Password')),
                    ]),
                ])
                ->action(function (array $data) {
                    $user = User::create([
                        'name'      => $data['name'],
                        'phone'     => $data['phone'],
                        'is_active' => true,
                        'role'      => 'admin',
                        'password'  => Hash::make($data['password'] ?: Str::random(16)),
                    ]);
                    $user->assignRole('admin');

                    Notification::make()->title(__('Admin created'))->success()->send();
                }),
        ];
    }
}
