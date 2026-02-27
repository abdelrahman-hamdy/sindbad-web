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
