<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Visit;

class DashboardController extends Controller
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
}
