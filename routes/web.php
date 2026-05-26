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

    Route::prefix('tanah')->group(function () {
        Route::get('/import', [TanahController::class, 'importForm'])->name('tanah.import.form');
        Route::post('/import/preview', [TanahController::class, 'importPreview'])->name('tanah.import.preview');
        Route::post('/import/execute', [TanahController::class, 'importExecute'])->name('tanah.import.execute');
    });
    Route::resource('tanah', TanahController::class);

    Route::prefix('peta')->group(function () {
        Route::get('/', [PetaController::class, 'index'])->name('peta.index');
        Route::get('/map', [PetaController::class, 'map'])->name('peta.map');
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
        Route::put('/{id}', [SismiopController::class, 'update'])->name('sismiop.update');
        Route::get('/{id}/edit', [SismiopController::class, 'edit'])->name('sismiop.edit');
    });
    Route::get('/print/{id}', [PrintController::class, 'generate'])->name('print.generate');
});

require __DIR__ . '/auth.php';

// Development routes for testing PostGIS connectivity and GeoJSON output
Route::get('/dev/postgis-test', function () {
    try {
        $conn = \Illuminate\Support\Facades\DB::connection('pgsql');

        // Check if gis table exists
        $table = $conn->selectOne("SELECT to_regclass('public.gis_bidang_tanah') as exists");
        if (! $table || ! $table->exists) {
            return response()->json(['error' => 'gis_bidang_tanah table not found. Run migrations on pgsql or switch DB_CONNECTION to pgsql.'], 400);
        }

        $versionRow = $conn->selectOne('SELECT PostGIS_Version() as version');
        $version = $versionRow->version ?? null;

        // Insert a dummy MultiPolygon (replace existing test row if present)
        $nop = 'TEST-NOP-001';
        $geojson = json_encode([
            'type' => 'MultiPolygon',
            'coordinates' => [[[
                [110.7001, -7.1001],
                [110.7002, -7.1001],
                [110.7002, -7.1002],
                [110.7001, -7.1002],
                [110.7001, -7.1001]
            ]]]
        ]);
        $properties = json_encode(['source' => 'dev-test']);

        $conn->delete('DELETE FROM gis_bidang_tanah WHERE nop = ?', [$nop]);
        $conn->insert("INSERT INTO gis_bidang_tanah (nop, properties, created_at, updated_at, geom) VALUES (?, ?::jsonb, now(), now(), ST_SetSRID(ST_GeomFromGeoJSON(?),4326))", [$nop, $properties, $geojson]);

        $row = $conn->selectOne('SELECT nop, properties, ST_AsGeoJSON(geom)::json as geometry FROM gis_bidang_tanah WHERE nop = ? LIMIT 1', [$nop]);

        return response()->json([
            'postgis_version' => $version,
            'sample' => [
                'nop' => $row->nop,
                'properties' => json_decode($row->properties, true),
                'geometry' => json_decode($row->geometry, true),
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
