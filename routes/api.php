<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InstallationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\TechnicianController;
use App\Http\Controllers\Api\CustomerHomeController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhooks (no auth)
Route::post('/webhooks/hypersender', [WebhookController::class, 'handleHyperSender']);

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/validate-phone', [AuthController::class, 'validatePhone'])->middleware('throttle:otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:otp');
    Route::post('/activate', [AuthController::class, 'activate']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureUserIsActive::class])->group(function () {

    // Profile
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/customer/home', [CustomerHomeController::class, 'home']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::get('/user', fn(\Illuminate\Http\Request $r) => $r->user());
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // New RESTful routes
    Route::patch('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);
    Route::patch('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Old routes kept as backwards-compat aliases
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/picture', [AuthController::class, 'uploadAvatar']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Service Requests (static routes before dynamic /{id})
    Route::get('/service-requests/my-orders', [RequestController::class, 'getMyOrders']);
    Route::get('/service-requests/check-eligibility', [RequestController::class, 'checkEligibility']);
    Route::post('/service-requests/bulk-delete', [RequestController::class, 'bulkDestroy']);
    Route::post('/service-requests', [RequestController::class, 'store'])->middleware(['check.pending', 'financial.eligibility']);
    Route::get('/service-requests', [RequestController::class, 'index']);
    Route::get('/service-requests/{id}', [RequestController::class, 'show']);
    Route::post('/service-requests/{id}/status', [RequestController::class, 'updateStatus'])
        ->middleware(\App\Http\Middleware\PreventTechnicianCompleteWithoutRating::class);
    Route::post('/service-requests/{id}/attachments', [RequestController::class, 'addAttachment']);
    Route::post('/service-requests/{id}/rating', [RequestController::class, 'submitRating'])->name('service_requests.rating');
    Route::delete('/service-requests/{id}', [RequestController::class, 'destroy']);

    // Installation Requests (static routes before dynamic /{id})
    Route::post('/installation-requests/bulk-delete', [InstallationController::class, 'bulkDestroy']);
    Route::post('/installation-requests', [InstallationController::class, 'store'])->middleware(['check.pending', 'financial.eligibility']);
    Route::get('/installation-requests', [InstallationController::class, 'index']);
    Route::get('/installation-requests/{id}', [InstallationController::class, 'show']);
    Route::post('/installation-requests/{id}/status', [InstallationController::class, 'updateStatus'])
        ->middleware(\App\Http\Middleware\PreventTechnicianCompleteWithoutRating::class);
    Route::post('/installation-requests/{id}/assign', [InstallationController::class, 'assignTechnician']);
    Route::post('/installation-requests/{id}/rating', [InstallationController::class, 'submitRating'])->name('installation_requests.rating');
    Route::put('/installation-requests/{id}/readiness', [InstallationController::class, 'updateReadiness']);
    Route::delete('/installation-requests/{id}', [InstallationController::class, 'destroy']);

    // Technician App
    Route::get('/technician/home', [TechnicianController::class, 'home']);
    Route::get('/technician/schedule', [RequestController::class, 'getMySchedule']);
    Route::post('/technician/service-requests/accept', [RequestController::class, 'acceptRequest']);
    Route::post('/technician/installation-requests/accept', [InstallationController::class, 'acceptRequest']);
    Route::post('/technician/location', [TechnicianController::class, 'updateLocation'])->middleware('throttle:location-update');
    Route::post('/technician/offline', [TechnicianController::class, 'goOffline']);
    Route::post('/technician/requests/upload-images', [TechnicianController::class, 'uploadImages']);
    Route::get('/technician/requests/images', [TechnicianController::class, 'getImages']);
    Route::delete('/technician/requests/images/{id}', [TechnicianController::class, 'deleteImage']);

    // Admin Routes (admin middleware applied to entire group)
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'dashboardStats']);

        // Static user routes before /{id}
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'storeUser']);
        Route::post('/users/bulk-delete', [AdminController::class, 'bulkDeleteUsers']);
        Route::get('/users/lookup/{phone}', [AdminController::class, 'lookupUserByPhone']);
        Route::get('/users/{id}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::post('/notifications/send', [AdminController::class, 'sendCustomNotification']);

        Route::post('/service-requests', [AdminController::class, 'createServiceRequest']);
        Route::post('/installation-requests', [AdminController::class, 'createInstallationRequest']);
        Route::post('/service-requests/{id}/assign', [RequestController::class, 'assignTechnician']);
        Route::post('/installation-requests/{id}/assign', [InstallationController::class, 'assignTechnician']);
        Route::get('/service-requests/{id}', [RequestController::class, 'show']);
        Route::get('/installation-requests/{id}', [InstallationController::class, 'show']);

        Route::get('/technicians/available', [AdminController::class, 'getAvailableTechnicians']);
        Route::get('/odoo/products', [AdminController::class, 'getOdooProducts']);

        Route::get('/reports/performance', [AdminController::class, 'getPerformanceReports']);
        Route::get('/reports/daily-activity', [AdminController::class, 'getDailyCompletedRequests']);
        Route::get('/reports/ratings', [AdminController::class, 'getRatings']);
    });
});
