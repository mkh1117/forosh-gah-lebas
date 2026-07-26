<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function index(Request $request)
    {

        $query = Order::with(['items.post', 'items.variant']);

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        }

        $orders = $query->latest()->get();

        return response()->json($orders, 200);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_name' => 'required|string|max:255',
            'phone'         => 'required|string|max:20',
            'address'       => 'required|string',
            'items'         => 'required|array|min:1',
            'items.*.post_id'            => 'required|exists:posts,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.color'              => 'nullable|string',
            'items.*.size'               => 'nullable|string',
            'items.*.price'              => 'required|integer',
            'items.*.quantity'           => 'required|integer|min:1',
        ]);

        $totalPrice = 0;
        foreach ($validated['items'] as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }


        $order = Order::create([
            'user_id'       => $request->user()?->id,
            'total_price'   => $totalPrice,
            'status'        => 'pending', // یا processing
            'receiver_name' => $validated['receiver_name'],
            'phone'         => $validated['phone'],
            'address'       => $validated['address'],
        ]);


        foreach ($validated['items'] as $item) {
            $order->items()->create([
                'post_id'            => $item['post_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'color'              => $item['color'] ?? null,
                'size'               => $item['size'] ?? null,
                'price'              => $item['price'],
                'quantity'           => $item['quantity'],
            ]);
        }

        return response()->json([
            'message' => 'سفارش با موفقیت ثبت شد.',
            'order'   => $order->load('items.post')
        ], 201);
    }

    public function show($id)
    {
        $order = Order::with(['items.post', 'items.variant'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'سفارش یافت نشد.'], 404);
        }

        return response()->json($order, 200);
    }
}
