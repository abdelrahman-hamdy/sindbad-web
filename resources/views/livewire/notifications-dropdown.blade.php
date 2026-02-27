<div
    class="relative"
    x-data="{
        open: false,
        init() {
            const setup = () => {
                window.Echo.private('user.{{ auth()->id() }}')
                    .listen('.notification.received', () => {
                        $wire.refreshNotifications();
                    });
            };
            if (window.Echo) {
                setup();
            } else {
                window.addEventListener('EchoLoaded', setup, { once: true });
            }
        }
    }"
    x-init="init()"
    @click.away="open = false"
>
    {{-- Bell button --}}
    <button
        @click="open = !open"
        class="relative flex items-center justify-center h-9 w-9 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 transition"
        aria-label="{{ __('Notifications') }}"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white leading-none">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute end-0 top-full mt-2 w-80 origin-top-end rounded-xl bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10 z-50"
        style="display:none"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Notifications') }}</h3>
            @if($unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                >
                    {{ __('Mark all read') }}
                </button>
            @endif
        </div>

        {{-- List --}}
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-white/5">
            @forelse($notifications as $notification)
                @php
                    $icon = match($notification->type ?? '') {
                        'new_request'        => 'heroicon-o-plus-circle',
                        'status_update'      => 'heroicon-o-arrow-path',
                        'request_assigned',
                        'technician_assigned' => 'heroicon-o-user-plus',
                        'new_message'        => 'heroicon-o-chat-bubble-left',
                        default              => 'heroicon-o-bell',
                    };
                @endphp
                <div
                    class="flex items-start gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer {{ $notification->read_at ? '' : 'bg-blue-50/60 dark:bg-blue-950/20' }}"
                    wire:click="markRead({{ $notification->id }})"
                >
                    {{-- Type icon --}}
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <x-dynamic-component :component="$icon" class="w-5 h-5 text-primary-500" />
                    </div>

                    {{-- Text --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $notification->title }}</p>
                        @if($notification->body)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $notification->body }}</p>
                        @endif
                        <p class="text-[11px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Unread dot --}}
                    @if(!$notification->read_at)
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                    @endif
                </div>
            @empty
                <div class="px-4 py-10 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-sm text-gray-400">{{ __('No notifications yet') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="border-t border-gray-100 dark:border-white/10 px-4 py-2.5 text-center">
            <a
                href="{{ \App\Filament\Resources\NotificationResource::getUrl('index') }}"
                class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline"
            >
                {{ __('View all notifications') }}
            </a>
        </div>
    </div>
</div>
