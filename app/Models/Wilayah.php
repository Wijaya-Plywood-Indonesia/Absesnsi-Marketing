<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    protected $fillable = [
        'kota',
        'kecamatan',
    ];

    /**
     * Ambil daftar kota unik (buat dropdown pertama).
     */
    public static function daftarKota(): array
    {
        return static::query()
            ->distinct()
            ->orderBy('kota')
            ->pluck('kota', 'kota')
            ->toArray();
    }

    /**
     * Ambil daftar kecamatan berdasarkan kota tertentu (buat dropdown kedua).
     */
    public static function daftarKecamatan(?string $kota): array
    {
        if (! $kota) {
            return [];
        }

        return static::query()
            ->where('kota', $kota)
            ->orderBy('kecamatan')
            ->pluck('kecamatan', 'kecamatan')
            ->toArray();
    }
}
