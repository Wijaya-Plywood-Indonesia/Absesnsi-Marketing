<?php

namespace App\Filament\Resources\Visits\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VisitInfolist
{
    // Samain dengan CHECKIN_RADIUS_METERS di app mobile.
    protected const CHECKIN_RADIUS_METERS = 100;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Foto Kunjungan')
                    ->schema([
                        ImageEntry::make('foto')
                            ->label('')
                            ->disk('public')
                            ->height(400)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                            ->imageSize(400)
                            ->url(fn ($record) => $record->foto ? asset('storage/'.$record->foto) : null)
                            ->openUrlInNewTab(),
                    ])
                    ->collapsible(false),

                Section::make('Informasi Kunjungan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Customer'),
                                TextEntry::make('user.name')
                                    ->label('Marketer'),
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d M Y'),
                                TextEntry::make('jam')
                                    ->label('Jam'),
                                TextEntry::make('hasil')
                                    ->label('Hasil Kunjungan')
                                    ->badge(),
                                TextEntry::make('accuracy')
                                    ->label('Akurasi GPS')
                                    ->suffix(' m'),
                            ]),
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),

                Section::make('Lokasi')
                    ->schema([
                        TextEntry::make('latitude')
                            ->label('Alamat')
                            ->formatStateUsing(fn ($record) => self::reverseGeocode($record->latitude, $record->longitude))
                            ->columnSpanFull(),

                        // Badge jarak ke toko: dalam radius / di luar radius,
                        // sama seperti validasi di app mobile saat check-in.
                        TextEntry::make('distance_to_store')
                            ->label('Jarak ke Toko')
                            ->state(function ($record) {
                                $distance = self::distanceToStore($record);

                                if ($distance === null) {
                                    return 'Koordinat toko tidak tersedia';
                                }

                                return round($distance).' m (maks. '.self::CHECKIN_RADIUS_METERS.' m)';
                            })
                            ->badge()
                            ->color(function ($record) {
                                $distance = self::distanceToStore($record);

                                if ($distance === null) {
                                    return 'gray';
                                }

                                return $distance <= self::CHECKIN_RADIUS_METERS ? 'success' : 'danger';
                            })
                            ->icon(function ($record) {
                                $distance = self::distanceToStore($record);

                                if ($distance === null) {
                                    return null;
                                }

                                return $distance <= self::CHECKIN_RADIUS_METERS
                                    ? 'heroicon-o-check-circle'
                                    : 'heroicon-o-exclamation-triangle';
                            })
                            ->columnSpanFull(),

                        // Peta 1 root <div>, semua logic di Alpine x-init (menghindari
                        // MultipleRootElementsDetectedException dari Livewire).
                        // Lingkaran biru = akurasi GPS kunjungan, lingkaran oranye
                        // putus-putus = radius toleransi check-in di sekitar toko.
                        ViewEntry::make('map')
                            ->label('')
                            ->view('filament.infolists.visit-map')
                            ->columnSpanFull(),

                        TextEntry::make('latitude')
                            ->label('')
                            ->formatStateUsing(fn () => 'Buka di Google Maps')
                            ->url(fn ($record) => "https://www.google.com/maps?q={$record->latitude},{$record->longitude}")
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->icon('heroicon-o-map-pin'),
                    ]),
            ]);
    }

    protected static function distanceToStore($record): ?float
    {
        $visitLat = $record->latitude;
        $visitLng = $record->longitude;
        $storeLat = $record->customer?->latitude;
        $storeLng = $record->customer?->longitude;

        if (! $visitLat || ! $visitLng || ! $storeLat || ! $storeLng) {
            return null;
        }

        return self::haversineMeters(
            (float) $visitLat,
            (float) $visitLng,
            (float) $storeLat,
            (float) $storeLng,
        );
    }

    protected static function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }

    protected static function reverseGeocode(?float $lat, ?float $lng): string
    {
        if (! $lat || ! $lng) {
            return '-';
        }

        $cacheKey = "geocode:{$lat},{$lng}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            // Nominatim MENOLAK (403) User-Agent generik seperti "Laravel", "PHP",
            // "curl", dll — lihat https://operations.osmfoundation.org/policies/nominatim/.
            // config('app.name') defaultnya "Laravel" kalau APP_NAME belum diisi di .env,
            // jadi jangan pakai itu langsung. Pakai string identitas aplikasi yang jelas.
            $response = Http::withHeaders([
                'User-Agent' => 'WijayaPlywoodAbsensi/1.0 (admin@wijayaplywood.com)',
            ])
                ->timeout(10)
                ->retry(2, 500)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if ($response->successful() && $response->json('display_name')) {
                $address = $response->json('display_name');
                // Cuma cache hasil yang BERHASIL — kalau gagal (mis. rate limit/network),
                // jangan dicache, supaya percobaan berikutnya bisa retry, bukan kejebak
                // fallback "lat, lng" selama 30 hari.
                Cache::put($cacheKey, $address, now()->addDays(30));

                return $address;
            }

            \Log::warning('Geocode gagal (non-success response)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Geocode gagal (exception): '.$e->getMessage());
        }

        return "{$lat}, {$lng}";
    }
}
