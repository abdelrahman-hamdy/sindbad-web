<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyUser(User $recipient, string $title, string $body, array $data = [], bool $push = true): void
    {
        // Always persist to DB
        try {
            $notification = Notification::create([
                'recipient_id' => $recipient->id,
                'title' => $title,
                'body' => $body,
                'type' => $data['type'] ?? null,
                'data' => $data,
            ]);

            if ($notification) {
                broadcast(new \App\Events\NotificationSent($recipient->id));
            }
        } catch (\Exception $e) {
            Log::error('Failed to persist notification: ' . $e->getMessage());
        }

        if ($push && $recipient->fcm_token) {
            $async = config('services.notifications.async', env('NOTIFICATION_ASYNC', false));

            if ($async) {
                SendPushNotificationJob::dispatch($recipient->fcm_token, $title, $body, $data);
            } else {
                $this->sendHttp($recipient->fcm_token, $title, $body, $data);
            }
        }
    }

    public function notifyRole(string $role, string $title, string $body, array $data = []): void
    {
        User::where('role', $role)->get()->each(function (User $user) use ($title, $body, $data) {
            $this->notifyUser($user, $title, $body, $data);
        });
    }

    public function notifyAdmins(string $title, string $body, array $data = []): void
    {
        $this->notifyRole('admin', $title, $body, $data);
    }

    public function sendHttp(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $credPath = storage_path('app/' . env('FCM_CREDENTIALS_PATH', 'firebase_credentials.json'));

        if (file_exists($credPath)) {
            try {
                $json = json_decode(file_get_contents($credPath), true);
                $projectId = $json['project_id'] ?? null;

                if (! $projectId) {
                    Log::error('FCM credentials missing project_id');
                    return false;
                }

                $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
                $creds = new ServiceAccountCredentials($scopes, $json);
                $token = $creds->fetchAuthToken();
                $accessToken = $token['access_token'] ?? null;

                if (empty($accessToken)) {
                    Log::error('Unable to obtain FCM access token');
                    return false;
                }

                $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
                $stringData = array_map(fn($v) => is_string($v) ? $v : json_encode($v), $data);

                $res = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'message' => [
                        'token' => $fcmToken,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => $stringData,
                    ],
                ]);

                if ($res->successful()) {
                    return true;
                }

                Log::error('FCM v1 send failed', ['status' => $res->status(), 'body' => $res->body()]);
                return false;
            } catch (\Exception $e) {
                Log::error('FCM v1 exception: ' . $e->getMessage());
            }
        }

        Log::warning('FCM credentials not found, push not sent');
        return false;
    }
}
