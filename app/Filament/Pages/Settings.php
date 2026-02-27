<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class Settings extends Page
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.settings';

    public static function getNavigationLabel(): string { return __('Settings'); }
    public static function getNavigationGroup(): ?string { return __('System'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public function getTitle(): string
    {
        return __('Settings');
    }

    // Dashboard settings
    public string $dashboard_default_filter = 'month';

    // Business rule settings (bound directly to Filament form fields)
    public bool $enforce_financial_eligibility = false;
    public bool $block_pending_requests = false;

    // Admin profile fields
    public string $admin_name = '';
    public string $admin_phone = '';
    public ?string $admin_password = null;
    public ?string $admin_password_confirmation = null;

    public function mount(): void
    {
        $this->dashboard_default_filter      = AppSetting::get('dashboard_default_filter', 'month');
        $this->enforce_financial_eligibility = AppSetting::bool('enforce_financial_eligibility');
        $this->block_pending_requests        = AppSetting::bool('block_pending_requests');

        $user             = auth()->user();
        $this->admin_name  = $user->name ?? '';
        $this->admin_phone = $user->phone ?? '';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([

                    Tab::make(__('Business Rules'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Toggle::make('enforce_financial_eligibility')
                                ->label(__('Enforce Financial Eligibility Check'))
                                ->helperText(__('Blocks customers from creating requests if they have outstanding Odoo dues.'))
                                ->onColor('success')
                                ->columnSpanFull(),
                            Toggle::make('block_pending_requests')
                                ->label(__('Block Pending Requests'))
                                ->helperText(__('Prevents customers from creating a new request while one is still active.'))
                                ->onColor('warning')
                                ->columnSpanFull(),
                            Actions::make([
                                PageAction::make('saveSettings')
                                    ->label(__('Save'))
                                    ->icon('heroicon-o-check')
                                    ->action('saveSettings'),
                            ]),
                        ])->columns(1),

                    Tab::make(__('Dashboard'))
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Select::make('dashboard_default_filter')
                                ->label(__('Default Stats Filter'))
                                ->helperText(__('The date range active by default when the dashboard stats widget loads.'))
                                ->options([
                                    'today' => __('Today'),
                                    'week'  => __('This Week'),
                                    'month' => __('This Month'),
                                    'all'   => __('All Time'),
                                ])
                                ->required(),
                            Actions::make([
                                PageAction::make('saveDashboardSettings')
                                    ->label(__('Save'))
                                    ->icon('heroicon-o-check')
                                    ->action('saveDashboardSettings'),
                            ]),
                        ])->columns(1),

                    Tab::make(__('My Profile'))
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Section::make(fn() => __('Editing your own profile') . ': ' . auth()->user()->name)
                                ->description(fn() => auth()->user()->phone)
                                ->icon('heroicon-o-shield-check')
                                ->schema([
                                    TextInput::make('admin_name')
                                        ->label(__('Name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('admin_phone')
                                        ->label(__('Phone'))
                                        ->required()
                                        ->maxLength(30),
                                    TextInput::make('admin_password')
                                        ->password()
                                        ->revealable()
                                        ->label(__('New Password'))
                                        ->helperText(__('Leave blank to keep the current password.'))
                                        ->nullable()
                                        ->minLength(8),
                                    TextInput::make('admin_password_confirmation')
                                        ->password()
                                        ->revealable()
                                        ->label(__('Confirm New Password'))
                                        ->nullable(),
                                ])->columns(2),
                            Actions::make([
                                PageAction::make('updateProfile')
                                    ->label(__('Save'))
                                    ->icon('heroicon-o-arrow-up-tray')
                                    ->action('updateProfile'),
                            ]),
                        ]),

                ])
                ->persistTabInQueryString(),
        ]);
    }

    public function saveDashboardSettings(): void
    {
        AppSetting::set('dashboard_default_filter', $this->dashboard_default_filter);
        Notification::make()->title(__('Dashboard settings saved'))->success()->send();
    }

    public function saveSettings(): void
    {
        AppSetting::set('enforce_financial_eligibility', $this->enforce_financial_eligibility ? 'true' : 'false');
        AppSetting::set('block_pending_requests', $this->block_pending_requests ? 'true' : 'false');

        Notification::make()->title(__('Settings saved successfully'))->success()->send();
    }

    public function updateProfile(): void
    {
        $this->validate([
            'admin_name'     => 'required|string|max:255',
            'admin_phone'    => 'required|string|max:30',
            'admin_password' => 'nullable|string|min:8|confirmed',
        ]);

        $user = auth()->user();
        $data = [
            'name'  => $this->admin_name,
            'phone' => $this->admin_phone,
        ];

        if (filled($this->admin_password)) {
            $data['password'] = Hash::make($this->admin_password);
        }

        $user->update($data);
        $this->admin_password              = null;
        $this->admin_password_confirmation = null;

        Notification::make()->title(__('Profile updated successfully'))->success()->send();
    }
}
