<?php

use App\Http\Controllers\GuardCheckController;
use App\Models\GuardCheck;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Welcome', [
        'samples' => GuardCheck::query()
            ->where('is_example', true)
            ->orderBy('id')
            ->get(['id', 'prompt', 'verdict', 'reasons']),
        'recentChecks' => GuardCheck::query()
            ->where('is_example', false)
            ->latest()
            ->limit(5)
            ->get(['id', 'prompt', 'verdict', 'reasons', 'answer', 'created_at']),
        'geminiEnabled' => filled(config('services.gemini.api_key')),
    ]);
})->name('home');

Route::post('/checks', [GuardCheckController::class, 'store'])->name('checks.store');
