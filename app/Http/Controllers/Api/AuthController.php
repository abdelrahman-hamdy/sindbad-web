<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Services\Auth\HyperSenderService;
use App\Services\Odoo\OdooServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Exception;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private HyperSenderService $hyperSender
    ) {}

    public function validatePhone(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        try {
            $customer = $this->authService->validateOdooUser($request->phone);
            $otpSent = $this->hyperSender->sendOtp($request->phone);

            Cache::put('auth_otp_' . $request->phone, [
                'name' => $customer['name'],
                'odoo_id' => $customer['id'],
                'phone' => $customer['phone'],
                'otp_sent' => (bool) $otpSent,
                'verified' => false,
            ], 600);

            return response()->json([
                'success' => true,
                'message' => __('OTP sent via WhatsApp'),
                'data' => [
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'otp_sent' => (bool) $otpSent,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string', 'code' => 'required|string']);

        $isValid = $this->hyperSender->validateOtp($request->phone, $request->code);

        if (! $isValid) {
            return response()->json(['success' => false, 'message' => __('Invalid or expired OTP')], 400);
        }

        $cacheKey = 'auth_otp_' . $request->phone;
        $cached = Cache::get($cacheKey);

        if (! $cached) {
            return response()->json(['success' => false, 'message' => __('Session expired. Please re-verify.')], 400);
        }

        $cached['verified'] = true;
        Cache::put($cacheKey, $cached, 600);

        return response()->json(['success' => true, 'message' => __('OTP verified successfully')]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $cached = Cache::get('auth_otp_' . $request->phone);

            if (! $cached || ! $cached['verified']) {
                return response()->json(['success' => false, 'message' => __('Session expired or not verified.')], 400);
            }

            $token = $this->authService->activateUser(
                $request->phone,
                $request->password,
                $cached['odoo_id'],
                $cached['name']
            );

            Cache::forget('auth_otp_' . $request->phone);

            return response()->json([
                'success' => true,
                'message' => __('Account activated successfully.'),
                'token' => $token,
                'user' => [
                    'name' => $cached['name'],
                    'phone' => $request->phone,
                    'is_active' => true,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        if (! auth()->attempt(['phone' => $request->phone, 'password' => $request->password])) {
            return response()->json(['success' => false, 'message' => __('Invalid credentials')], 401);
        }

        $user = auth()->user();

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => __('Account not active')], 403);
        }

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Login successful'),
            'token' => $user->createToken('auth_token')->plainTextToken,
            'type' => ucfirst($user->role),
            'user' => $user,
        ]);
    }

    public function profile(Request $request, OdooServiceInterface $odoo)
    {
        $user = $request->user();
        $financials = null;
        $stats = null;

        if ($user->isCustomer()) {
            if ($user->odoo_id) {
                $orders = $odoo->getCustomerOrders($user->odoo_id, $user->phone);
                $financials = [
                    'orders'  => $orders,
                    'summary' => [
                        'total_amount' => collect($orders)->sum('amount_total'),
                        'total_due'    => collect($orders)->sum('amount_due'),
                    ],
                ];
            }

            $stats = [
                'total_requests'     => $user->requests()->count(),
                'completed_requests' => $user->requests()->where('status', 'completed')->count(),
                'pending_requests'   => $user->requests()->whereIn('status', ['pending', 'assigned', 'on_way', 'in_progress'])->count(),
                'member_since'       => $user->created_at->format('Y'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user'       => $user,
                'financials' => $financials,
                'stats'      => $stats,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->update(['fcm_token' => null]);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => __('Logged out successfully')]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $cacheKey = 'auth_otp_' . $request->phone;
            $cached = Cache::get($cacheKey);

            if (! $cached || ! $cached['verified']) {
                return response()->json(['success' => false, 'message' => __('Session expired or not verified.')], 400);
            }

            $user = \App\Models\User::where('phone', $request->phone)->firstOrFail();
            $user->update(['password' => bcrypt($request->password)]);

            Cache::forget($cacheKey);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => __('Password reset successfully.'),
                'token'   => $token,
                'user'    => $user,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        $user = $request->user();
        $file = $request->file('avatar');
        $path = $file->store('avatars', 'public');
        $url  = Storage::url($path);

        $user->update(['avatar_url' => $url]);

        return response()->json([
            'success'    => true,
            'avatar_url' => $url,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json(['success' => true, 'message' => __('Profile updated successfully'), 'data' => $user]);
    }
}
