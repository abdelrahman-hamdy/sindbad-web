<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleHyperSender(Request $request)
    {
        Log::info('HyperSender webhook received', $request->all());

        // HyperSender sends delivery confirmations and OTP responses
        // No action needed — OTP validation is handled by the API call
        return response()->json(['success' => true]);
    }
}
