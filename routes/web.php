<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard.index');

// Important: Breeze Auth controllers use route('dashboard') by default. We align them to dashboard.index in controllers and AppServiceProvider.

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Help routes (stub routes to prevent errors)
    Route::get('/help', function () {
        return redirect()->route('dashboard.index');
    })->name('help.index');

    Route::get('/help/search', function () {
        return response()->json([
            'success' => true,
            'results' => [],
        ]);
    })->name('help.search');

    Route::get('/help/contextual', function () {
        return response()->json([
            'success' => true,
            'articles' => [],
        ]);
    })->name('help.contextual');

    // Notification routes (stub routes to prevent errors)
    Route::get('/notifications', function () {
        return response()->json([]);
    })->name('notifications.index');

    Route::get('/notifications/count', function () {
        return response()->json(['count' => 0]);
    })->name('notifications.count');

    Route::post('/notifications/read-all', function () {
        return response()->json(['success' => true]);
    })->name('notifications.read-all');

    Route::post('/notifications/{id}/read', function ($id) {
        return response()->json(['success' => true]);
    })->name('notifications.read');

    // Reports route (stub route to prevent errors)
    Route::get('/reports', function () {
        return redirect()->route('dashboard.index');
    })->name('reports.index');
});

require __DIR__.'/auth.php';
