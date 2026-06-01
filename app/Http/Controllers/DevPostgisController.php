<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DevPostgisController extends Controller
{
    public function show()
    {
        try {
            $conn = DB::connection('pgsql');

            $table = $conn->selectOne("SELECT to_regclass('public.gis_bidang_tanah') as exists");
            if (! $table || ! $table->exists) {
                return response()->json(['error' => 'gis_bidang_tanah table not found. Run migrations on pgsql or switch DB_CONNECTION to pgsql.'], 400);
            }

            $versionRow = $conn->selectOne('SELECT PostGIS_Version() as version');
            $countRow = $conn->selectOne('SELECT COUNT(*) as count FROM gis_bidang_tanah');

            return response()->json([
                'postgis_version' => $versionRow->version ?? null,
                'gis_bidang_tanah_count' => (int) ($countRow->count ?? 0),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
