<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Visit;
use App\Services\ImageWatermarkService;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    const CHECKIN_RADIUS_METERS = 100;

    public function saveCheckin(Request $request, ImageWatermarkService $watermarkService)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'hasil' => 'required|string',
            'catatan' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|string',
            'alamat_text' => 'nullable|string',
            'foto' => 'nullable|image|max:5120',
        ]);

        $existing = Visit::where('customer_id', $request->customer_id)
            ->where('user_id', auth()->id())
            ->where('tanggal', now()->toDateString())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan kunjungan ke toko ini hari ini. Silakan edit kunjungan yang sudah ada.',
                'existing_visit_id' => $existing->id,
            ], 422);
        }

        $customer = Customer::findOrFail($request->customer_id);

        // Dulu: check-in di luar radius DITOLAK (return 422).
        // Sekarang: check-in tetap disimpan, tapi ditandai is_outside_area = true
        // supaya bisa dipantau admin tanpa mengganggu kerja marketer di lapangan.
        $isOutsideArea = false;

        if ($customer->latitude && $customer->longitude) {
            $distance = $this->distanceMeters(
                (float) $request->latitude,
                (float) $request->longitude,
                (float) $customer->latitude,
                (float) $customer->longitude,
            );

            $isOutsideArea = $distance > self::CHECKIN_RADIUS_METERS;
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $watermarkService->processCheckinPhoto(
                file: $request->file('foto'),
                watermarkLines: [
                    $customer->name,
                    now()->format('d M Y, H:i').' WIB',
                    $request->alamat_text
                        ?: 'Lat: '.number_format((float) $request->latitude, 6).', Lng: '.number_format((float) $request->longitude, 6),
                ],
            );
        }

        $visit = Visit::create([
            'customer_id' => $request->customer_id,
            'user_id' => auth()->id(),
            'tanggal' => now()->toDateString(),
            'jam' => now()->format('H:i'),
            'hasil' => $request->hasil,
            'catatan' => $request->catatan ?: 'Tanpa catatan tambahan.',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'foto' => $fotoPath,
            'is_outside_area' => $isOutsideArea,
        ]);

        return response()->json([
            'success' => true,
            'visit' => $visit->load('customer'),
            'is_outside_area' => $isOutsideArea,
        ]);
    }

    public function todayVisit(Customer $customer)
    {
        $visit = Visit::where('customer_id', $customer->id)
            ->where('user_id', auth()->id())
            ->where('tanggal', now()->toDateString())
            ->latest('id')
            ->first();

        if (! $visit) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'visit' => [
                'id' => $visit->id,
                'hasil' => $visit->hasil,
                'catatan' => $visit->catatan,
                'foto' => $visit->foto ? asset('storage/'.$visit->foto) : null,
                'latitude' => $visit->latitude,
                'longitude' => $visit->longitude,
                'is_outside_area' => $visit->is_outside_area,
            ],
        ]);
    }

    public function updateCheckin(Request $request, Visit $visit, ImageWatermarkService $watermarkService)
    {
        if ($visit->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'hasil' => 'required|string',
            'catatan' => 'nullable|string',
            'alamat_text' => 'nullable|string',
            'foto' => 'nullable|image|max:5120',
        ]);

        $data = [
            'hasil' => $request->hasil,
            'catatan' => $request->catatan ?: 'Tanpa catatan tambahan.',
        ];

        if ($request->hasFile('foto')) {
            $data['foto'] = $watermarkService->processCheckinPhoto(
                file: $request->file('foto'),
                watermarkLines: [
                    $visit->customer->name,
                    now()->format('d M Y, H:i').' WIB',
                    $request->alamat_text
                        ?: 'Lat: '.number_format((float) $visit->latitude, 6).', Lng: '.number_format((float) $visit->longitude, 6),
                ],
            );
        }

        $visit->update($data);

        return response()->json([
            'success' => true,
            'visit' => $visit->fresh()->load('customer'),
        ]);
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }
}
