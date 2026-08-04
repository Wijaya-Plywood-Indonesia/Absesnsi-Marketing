<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
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

        $order = DB::transaction(function () use ($request) {
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
}
