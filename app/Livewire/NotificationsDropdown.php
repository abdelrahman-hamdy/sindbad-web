<?php

namespace App\Livewire;

use App\Models\Notification;
use Livewire\Component;

class NotificationsDropdown extends Component
{
    public function markAllRead(): void
    {
        Notification::where('recipient_id', auth()->id())->unread()->update(['read_at' => now()]);
    }

    public function markRead(int $id): void
    {
        Notification::where('recipient_id', auth()->id())->find($id)?->markAsRead();
    }

    public function refreshNotifications(): void
    {
        // Livewire re-renders the component on next request
    }

    public function render()
    {
        $userId = auth()->id();

        return view('livewire.notifications-dropdown', [
            'notifications' => Notification::where('recipient_id', $userId)->latest()->take(20)->get(),
            'unreadCount'   => Notification::where('recipient_id', $userId)->whereNull('read_at')->count(),
        ]);
    }
}
