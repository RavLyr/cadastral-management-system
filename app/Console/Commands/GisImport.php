<?php

namespace App\Console\Commands;

use App\Support\NopNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GisImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gis:import {path : Path to GeoJSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import GeoJSON FeatureCollection into gis_bidang_tanah (PostGIS)';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (! $data || ! isset($data['type']) || strtolower($data['type']) !== 'featurecollection' || ! isset($data['features'])) {
            $this->error('File is not a valid GeoJSON FeatureCollection.');
            return 1;
        }

        $success = 0;
        $failed = 0;
        $missingNop = 0;
        $invalidLength = 0;
        $supportsNopRaw = Schema::hasColumn('gis_bidang_tanah', 'nop_raw');

        foreach ($data['features'] as $feature) {
            try {
                $properties = $feature['properties'] ?? [];

                $nopRaw = $this->extractNop($properties);
                $nop = NopNormalizer::normalize($nopRaw);

                if (! $nop) {
                    $missingNop++;
                    continue;
                }

                if (strlen($nop) !== 18) {
                    $invalidLength++;
                }

                if (! isset($feature['geometry']) || ! isset($feature['geometry']['type']) || ! isset($feature['geometry']['coordinates'])) {
                    $failed++;
                    continue;
                }

                $geom = $feature['geometry'];
                $geomType = strtolower($geom['type']);
                $geomObj = $geom;

                // normalize Polygon to MultiPolygon
                if ($geomType === 'polygon') {
                    $geomObj = [
                        'type' => 'MultiPolygon',
                        'coordinates' => [ $geom['coordinates'] ],
                    ];
                } elseif ($geomType === 'multipolygon') {
                    $geomObj = $geom;
                } else {
                    // skip non-polygon types
                    $failed++;
                    continue;
                }

                $geomJson = json_encode($geomObj);
                $propertiesJson = json_encode($properties);

                // check existing by normalized NOP
                $exists = DB::connection('pgsql')->selectOne('SELECT id FROM gis_bidang_tanah WHERE nop = ? LIMIT 1', [$nop]);

                if ($exists) {
                    if ($supportsNopRaw) {
                        DB::connection('pgsql')->update(
                            'UPDATE gis_bidang_tanah SET nop_raw = ?, properties = ?::jsonb, geom = ST_SetSRID(ST_GeomFromGeoJSON(?),4326), updated_at = now() WHERE nop = ?',
                            [$nopRaw, $propertiesJson, $geomJson, $nop]
                        );
                    } else {
                        DB::connection('pgsql')->update(
                            'UPDATE gis_bidang_tanah SET properties = ?::jsonb, geom = ST_SetSRID(ST_GeomFromGeoJSON(?),4326), updated_at = now() WHERE nop = ?',
                            [$propertiesJson, $geomJson, $nop]
                        );
                    }
                } else {
                    if ($supportsNopRaw) {
                        DB::connection('pgsql')->insert(
                            'INSERT INTO gis_bidang_tanah (nop, nop_raw, properties, geom, created_at, updated_at) VALUES (?, ?, ?::jsonb, ST_SetSRID(ST_GeomFromGeoJSON(?),4326), now(), now())',
                            [$nop, $nopRaw, $propertiesJson, $geomJson]
                        );
                    } else {
                        DB::connection('pgsql')->insert(
                            'INSERT INTO gis_bidang_tanah (nop, properties, geom, created_at, updated_at) VALUES (?, ?::jsonb, ST_SetSRID(ST_GeomFromGeoJSON(?),4326), now(), now())',
                            [$nop, $propertiesJson, $geomJson]
                        );
                    }
                }

                $success++;
            } catch (\Throwable $e) {
                $this->line('Error importing feature: ' . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Import finished. Success: {$success}, Failed: {$failed}, Missing NOP: {$missingNop}, Invalid NOP length: {$invalidLength}");
        return 0;
    }

    private function extractNop(array $properties): ?string
    {
        if (empty($properties)) {
            return null;
        }

        $normalized = [];
        foreach ($properties as $key => $value) {
            $k = strtolower(trim((string) $key));
            $normalized[$k] = $value;
        }

        $priorityKeys = ['nop', 'd_nop', 'nop_pbb', 'no_pbb', 'kd_nop'];
        foreach ($priorityKeys as $key) {
            if (array_key_exists($key, $normalized)) {
                $value = trim((string) $normalized[$key]);
                return $value !== '' ? $value : null;
            }
        }

        foreach ($normalized as $key => $value) {
            if (str_contains($key, 'nop')) {
                $nop = trim((string) $value);
                return $nop !== '' ? $nop : null;
            }
        }

        return null;
    }
}
