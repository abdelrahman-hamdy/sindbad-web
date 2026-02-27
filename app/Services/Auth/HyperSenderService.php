<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HyperSenderService
{
    protected string $token;
    protected string $instanceUuid;
    protected string $baseUrl = 'https://app.hypersender.com/api/v1';

    public function __construct()
    {
        $this->token = env('HYPERSENDER_API_TOKEN', '');
        $this->instanceUuid = env('HYPERSENDER_INSTANCE_UUID', '');
    }

    public function sendOtp(string $phone): bool
    {
        try {
            $response = Http::withToken($this->token)->post("{$this->baseUrl}/instance/{$this->instanceUuid}/send-otp", [
                'phone' => $phone,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('HyperSender sendOtp failed', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('HyperSender sendOtp exception: ' . $e->getMessage());
            return false;
        }
    }

    public function validateOtp(string $phone, string $code): bool
    {
        try {
            $response = Http::withToken($this->token)->post("{$this->baseUrl}/instance/{$this->instanceUuid}/validate-otp", [
                'phone' => $phone,
                'code' => $code,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['valid'] ?? $data['success'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('HyperSender validateOtp exception: ' . $e->getMessage());
            return false;
        }
    }
}
