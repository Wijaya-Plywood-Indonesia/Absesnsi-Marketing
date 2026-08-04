<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Visit;
use App\Models\Wilayah;
use App\Services\ImageWatermarkService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MarketerController extends Controller
{
    public function index()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->role === 'admin') {
            return redirect('/admin');
        }

        if ($user->role !== 'marketing') {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Hanya akun Marketing yang dapat mengakses aplikasi ini.']);
        }

        $customers = Customer::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        $visits = Visit::with('customer')
            ->where('user_id', $user->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam', 'desc')
            ->get();

        $orders = Order::with(['customer', 'orderItems.product'])
            ->where('user_id', $user->id)
            ->orderBy('order_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $products = Product::orderBy('name')->get();

        $dailyTarget = $user->daily_target;
        $todayVisitCount = $visits->where('tanggal', now()->toDateString())->count();

        return view('marketer.dashboard', compact(
            'customers',
            'visits',
            'orders',
            'user',
            'products',
            'dailyTarget',
            'todayVisitCount'
        ));
    }

    public function showLogin()
    {
        if (auth()->check()) {
            if (auth()->user()->role === 'admin') {
                return redirect('/admin');
            }

            return redirect()->route('dashboard');
        }

        return view('marketer.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt($credentials)) {
            $request->session()->regenerate();

            if (auth()->user()->role === 'admin') {
                return redirect('/admin');
            }

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

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
            \Storage::disk('public')->delete($customer->foto);
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

        while (\Storage::disk($disk)->exists($folder.'/'.$filename)) {
            $filename = $baseSlug.'-'.$counter.'.'.$extension;
            $counter++;
        }

        $file->storeAs($folder, $filename, $disk);

        return $folder.'/'.$filename;
    }

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

    public function saveOrder(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        $order = \DB::transaction(function () use ($request) {
            $latestOrder = Order::lockForUpdate()->latest()->first();
            $counter = 1;
            if ($latestOrder) {
                $num = (int) str_replace('ORD-', '', $latestOrder->order_no);
                $counter = $num + 1;
            }
            $orderNo = 'ORD-'.str_pad($counter, 4, '0', STR_PAD_LEFT);

            $order = Order::create([
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'order_no' => $orderNo,
                'order_date' => $request->order_date ?: now()->toDateString(),
                'catatan' => $request->catatan,
            ]);

            $products = Product::whereIn('id', collect($request->items)->pluck('product_id'))
                ->get()
                ->keyBy('id');

            $order->orderItems()->createMany(
                collect($request->items)->map(fn ($item) => [
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit' => $products[$item['product_id']]->unit,
                ])
            );

            return $order;
        });

        return response()->json([
            'success' => true,
            'order' => $order->load(['customer', 'orderItems.product']),
        ]);
    }

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
