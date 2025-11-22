<?php

use App\Http\Controllers\PencarianController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\TanahController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SismiopController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Route::has('login')) {
        return redirect()->route('login');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('/tanah', TanahController::class);
    Route::get('/peta', [PetaController::class, 'index'])->name('peta.index');
    Route::post('/peta', [PetaController::class, 'store'])->name('peta.store');
    Route::delete('/peta/{id}', [PetaController::class, 'destroy'])->name('peta.destroy');
    Route::get('/pencarian', [PencarianController::class, 'index'])->name('pencarian.index');
    Route::get('/sismiop', [SismiopController::class, 'index'])->name('sismiop.index');
    Route::post('/sismiop', [SismiopController::class, 'import'])->name('sismiop.import');
    Route::post('/sismiop/commit', [SismiopController::class, 'commit'])->name('sismiop.commit');
    Route::delete('/sismiop/clear', [SismiopController::class, 'clear'])->name('sismiop.clear');
    Route::delete('/sismiop/{id}', [SismiopController::class, 'destroy'])->name('sismiop.destroy');
});

require __DIR__ . '/auth.php';
