<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        'items.*.product_variant_id' => 'required|exists:product_variants,id',
        'items.*.quantity'           => 'required|integer|min:1',
    ]);

    try {
        $order = DB::transaction(function () use ($request, $validated) {
            $totalPrice = 0;
            $itemsToCreate = [];

            foreach ($validated['items'] as $itemData) {

                $variant = ProductVariant::where('id', $itemData['product_variant_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    throw new Exception("تنوع محصول مورد نظر یافت نشد.");
                }


                if ($variant->stock < $itemData['quantity']) {
                    throw new Exception("موجودی محصول '{$variant->product->title}' کافی نیست. موجودی فعلی: {$variant->stock}");
                }


                $itemPrice = $variant->price;
                $subtotal = $itemPrice * $itemData['quantity'];
                $totalPrice += $subtotal;


                $variant->decrement('stock', $itemData['quantity']);


                $itemsToCreate[] = [
                    'post_id'            => $variant->post_id,
                    'product_variant_id' => $variant->id,
                    'color'              => $variant->color,
                    'size'               => $variant->size,
                    'price'              => $itemPrice,
                    'quantity'           => $itemData['quantity'],
                ];
            }


            $order = Order::create([
                'user_id'       => $request->user()?->id,
                'total_price'   => $totalPrice,
                'status'        => 'pending',
                'receiver_name' => $validated['receiver_name'],
                'phone'         => $validated['phone'],
                'address'       => $validated['address'],
            ]);


            $order->items()->createMany($itemsToCreate);

            return $order;
        });

        return response()->json([
            'message' => 'سفارش با موفقیت ثبت شد.',
            'order'   => $order->load('items.post')
        ], 201);

    } catch (Exception $e) {

        return response()->json([
            'message' => $e->getMessage()
        ], 400);
    }
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
