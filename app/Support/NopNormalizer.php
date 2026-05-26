<?php

namespace App\Support;

class NopNormalizer
{
    public static function normalize(?string $nop): ?string
    {
        if ($nop === null) {
            return null;
        }

        $trimmed = trim($nop);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed);

        return $digits !== '' ? $digits : null;
    }

    public static function formatDisplay(?string $nop): ?string
    {
        $normalized = self::normalize($nop);

        if ($normalized === null) {
            return null;
        }

        if (strlen($normalized) !== 18) {
            return $normalized;
        }

        return sprintf(
            '%s.%s.%s.%s.%s-%s.%s',
            substr($normalized, 0, 2),
            substr($normalized, 2, 2),
            substr($normalized, 4, 3),
            substr($normalized, 7, 3),
            substr($normalized, 10, 3),
            substr($normalized, 13, 4),
            substr($normalized, 17, 1)
        );
    }

    public static function parse(?string $nop): array
    {
        $normalized = self::normalize($nop);

        if ($normalized === null || strlen($normalized) !== 18) {
            return [
                'valid' => false,
                'normalized' => $normalized,
                'provinsi' => null,
                'kabupaten' => null,
                'kecamatan' => null,
                'desa' => null,
                'blok' => null,
                'nomor_objek' => null,
                'kode_khusus' => null,
            ];
        }

        return [
            'valid' => true,
            'normalized' => $normalized,
            'provinsi' => substr($normalized, 0, 2),
            'kabupaten' => substr($normalized, 2, 2),
            'kecamatan' => substr($normalized, 4, 3),
            'desa' => substr($normalized, 7, 3),
            'blok' => substr($normalized, 10, 3),
            'nomor_objek' => substr($normalized, 13, 4),
            'kode_khusus' => substr($normalized, 17, 1),
        ];
    }
}
