<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/admin/set-locale', function (Request $request) {
    $locale = in_array($request->locale, ['ar', 'en']) ? $request->locale : config('app.locale');
    session(['locale' => $locale]);
    return back();
})->middleware('web')->name('set-locale');

// Resolves shortened Google Maps URLs (maps.app.goo.gl, goo.gl/maps) to their final URL
Route::post('/admin/resolve-url', function (Request $request) {
    $request->validate(['url' => 'required|url|max:2048']);

    try {
        $response = \Illuminate\Support\Facades\Http::withOptions([
            'allow_redirects' => ['max' => 10],
            'timeout' => 8,
        ])->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->get($request->url);

        return response()->json(['url' => (string) $response->effectiveUri()]);
    } catch (\Exception) {
        return response()->json(['error' => 'Could not resolve URL'], 422);
    }
})->middleware(['web', 'auth'])->name('admin.resolve-url');
