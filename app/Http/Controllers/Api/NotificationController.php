<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('recipient_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markRead(Request $request)
    {
        $request->validate(['ids' => 'nullable|array', 'ids.*' => 'integer']);

        $query = Notification::where('recipient_id', $request->user()->id)->whereNull('read_at');

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->ids);
        }

        $query->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => __('Status updated')]);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['success' => true, 'data' => ['count' => $count]]);
    }

    public function destroy(Request $request, int $id)
    {
        $notification = Notification::where('recipient_id', $request->user()->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json(null, 204);
    }
}
