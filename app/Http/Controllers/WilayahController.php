<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function getKota()
    {
        return response()->json(
            Wilayah::daftarKota()
        );
    }

    public function getKecamatan(Request $request)
    {
        $request->validate([
            'kota' => 'required|string|max:255',
        ]);

        return response()->json(
            Wilayah::daftarKecamatan($request->kota)
        );
    }
}
