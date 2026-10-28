<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Services\ImageWatermarkService;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    /**
     * Simpan absen (masuk atau keluar).
     */
    public function saveAbsensi(Request $request, ImageWatermarkService $watermarkService)
    {
        $request->validate([
            'tipe'       => 'required|in:masuk,keluar',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
            'accuracy'   => 'nullable|string',
            'alamat_text' => 'nullable|string',
            'foto'       => 'nullable|image|max:5120',
        ]);

        $userId  = auth()->id();
        $tanggal = now()->toDateString();
        $tipe    = $request->tipe;

        // Cegah duplikat absen tipe yang sama pada hari yang sama
        $existing = Absensi::where('user_id', $userId)
            ->where('tanggal', $tanggal)
            ->where('tipe', $tipe)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan absen '.$tipe.' hari ini.',
                'absensi' => $existing,
            ], 422);
        }

        // Jika tipe keluar, pastikan sudah absen masuk dulu
        if ($tipe === 'keluar') {
            $absenMasuk = Absensi::where('user_id', $userId)
                ->where('tanggal', $tanggal)
                ->where('tipe', 'masuk')
                ->first();

            if (! $absenMasuk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum absen masuk hari ini.',
                ], 422);
            }
        }

        $labelTipe = $tipe === 'masuk' ? 'Absen Masuk' : 'Absen Keluar';

        $fotoPath = null;
        $fotoFileOrPath = $request->hasFile('foto')
            ? $request->file('foto')
            : $request->input('foto_temp_path');

        if ($fotoFileOrPath) {
            $fotoPath = $watermarkService->processCheckinPhoto(
                file: $fotoFileOrPath,
                folder: 'absensis',
                watermarkLines: [
                    $labelTipe.' — '.auth()->user()->name,
                    now()->format('d M Y, H:i').' WIB',
                    $request->alamat_text
                        ?: 'Lat: '.number_format((float) $request->latitude, 6).', Lng: '.number_format((float) $request->longitude, 6),
                ],
            );

            // Hapus file temp jika menggunakan temp path
            if (is_string($fotoFileOrPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($fotoFileOrPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($fotoFileOrPath);
            }
        }

        $absensi = Absensi::create([
            'user_id'  => $userId,
            'tanggal'  => $tanggal,
            'jam'      => now()->format('H:i'),
            'tipe'     => $tipe,
            'foto'     => $fotoPath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'catatan'  => $request->catatan,
        ]);

        return response()->json([
            'success'  => true,
            'absensi'  => $absensi,
        ]);
    }

    /**
     * Status absen hari ini untuk user yang sedang login.
     * Dipakai oleh Vue untuk menentukan apakah perlu pop-up atau alert.
     */
    public function todayStatus()
    {
        $userId  = auth()->id();
        $tanggal = now()->toDateString();

        $masuk  = Absensi::where('user_id', $userId)->where('tanggal', $tanggal)->where('tipe', 'masuk')->first();
        $keluar = Absensi::where('user_id', $userId)->where('tanggal', $tanggal)->where('tipe', 'keluar')->first();

        return response()->json([
            'masuk'  => $masuk  ? ['jam' => $masuk->jam,  'foto' => $masuk->foto  ? asset('storage/'.$masuk->foto)  : null] : null,
            'keluar' => $keluar ? ['jam' => $keluar->jam, 'foto' => $keluar->foto ? asset('storage/'.$keluar->foto) : null] : null,
        ]);
    }
}
