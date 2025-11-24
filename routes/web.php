<?php

use App\Http\Controllers\PencarianController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\TanahController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SismiopController;
use App\Http\Controllers\PrintController;
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
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::resource('tanah', TanahController::class);

    Route::prefix('peta')->group(function () {
        Route::get('/', [PetaController::class, 'index'])->name('peta.index');
        Route::post('/', [PetaController::class, 'store'])->name('peta.store');
        Route::delete('/{id}', [PetaController::class, 'destroy'])->name('peta.destroy');
    });

    Route::get('/pencarian', [PencarianController::class, 'index'])->name('pencarian.index');

    Route::prefix('sismiop')->group(function () {
        Route::get('/', [SismiopController::class, 'index'])->name('sismiop.index');
        Route::post('/', [SismiopController::class, 'import'])->name('sismiop.import');
        Route::post('/commit', [SismiopController::class, 'commit'])->name('sismiop.commit');
        Route::delete('/clear', [SismiopController::class, 'clear'])->name('sismiop.clear');
        Route::delete('/{id}', [SismiopController::class, 'destroy'])->name('sismiop.destroy');
    });

    Route::get('/print/{id}', [PrintController::class, 'generate'])->name('print.generate');
});

require __DIR__ . '/auth.php';
