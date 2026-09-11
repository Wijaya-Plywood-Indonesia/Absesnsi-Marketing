<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function uploadTemp(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|max:5120',
        ]);

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('temp', 'public');
            return response()->json([
                'url' => asset('storage/' . $path),
                'path' => $path
            ]);
        }
        
        return response()->json(['error' => 'Upload gagal'], 400);
    }
}
