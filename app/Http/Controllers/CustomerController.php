<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function saveCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'required|string|max:1000',
            'kota' => 'required|string|max:255|exists:wilayahs,kota',
            'kecamatan' => [
                'required',
                'string',
                'max:255',
                Rule::exists('wilayahs', 'kecamatan')->where('kota', $request->kota),
            ],
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'pola' => 'required|string',
            'jenis' => 'required|string',
            'foto' => 'nullable|image|max:5120',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $this->storeCustomerPhoto($request->file('foto'), $request->name);
        }

        $customer = Customer::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'kota' => $request->kota,
            'kecamatan' => $request->kecamatan,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'pola' => $request->pola,
            'jenis' => $request->jenis,
            'status' => 'Baru',
            'foto' => $fotoPath,
        ]);

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }

    public function updateCustomerPhoto(Request $request, Customer $customer)
    {
        if ($customer->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'foto' => 'required|image|max:5120',
        ]);

        // Hapus foto lama biar tidak numpuk file yatim di storage
        if ($customer->foto) {
            Storage::disk('public')->delete($customer->foto);
        }

        $fotoPath = $this->storeCustomerPhoto($request->file('foto'), $customer->name);

        $customer->update(['foto' => $fotoPath]);

        return response()->json([
            'success' => true,
            'customer' => $customer->fresh(),
        ]);
    }

    /**
     * Simpan foto customer dengan nama berbasis nama toko.
     * Kalau nama file sudah ada, tambahkan suffix angka: nama-1.jpg, nama-2.jpg, dst.
     */
    private function storeCustomerPhoto(UploadedFile $file, string $customerName): string
    {
        $disk = 'public';
        $folder = 'customers';

        $baseSlug = Str::slug($customerName) ?: 'toko';
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        $filename = $baseSlug.'.'.$extension;
        $counter = 1;

        while (Storage::disk($disk)->exists($folder.'/'.$filename)) {
            $filename = $baseSlug.'-'.$counter.'.'.$extension;
            $counter++;
        }

        $file->storeAs($folder, $filename, $disk);

        return $folder.'/'.$filename;
    }
}
