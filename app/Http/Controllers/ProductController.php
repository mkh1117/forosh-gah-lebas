<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with('variants');

        // فیلتر دسته‌بندی
        if ($request->category && $request->category !== 'همه') {
            $query->where('category', $request->category);
        }

        // فیلتر سایز
        if ($request->sizes) {
            $sizes = explode(',', $request->sizes);
            $query->whereHas('variants', fn($q) => $q->whereIn('size', $sizes));
        }

        // فیلتر رنگ
        if ($request->colors) {
            $colors = explode(',', $request->colors);
            $query->whereHas('variants', fn($q) => $q->whereIn('color', $colors));
        }

        // فیلتر تخفیف
        if ($request->sale_only === 'true') {
            $today = now()->toDateString();
            $query->where('has_discount', true)
                  ->where(fn($q) => $q->whereNull('discount_start')->orWhere('discount_start', '<=', $today))
                  ->where(fn($q) => $q->whereNull('discount_end')->orWhere('discount_end', '>=', $today));
        }

        // فیلتر قیمت
        if ($request->min_price !== null) {
            $query->where('default_price', '>=', $request->min_price);
        }
        if ($request->max_price !== null) {
            $query->where('default_price', '<=', $request->max_price);
        }

        $products = $query->get()->map(fn($p) => $this->formatProduct($p));

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Post::with('variants')->findOrFail($id);
        return response()->json($this->formatProduct($product));
    }

    private function formatProduct(Post $p): array
    {
        return [
            'id'               => $p->id,
            'title'            => $p->title,
            'category'         => $p->category,
            'price'            => $p->default_price,
            'final_price'      => $p->final_price,
            'image'            => $p->picture,
            'sizes'            => $p->sizes,
            'colors'           => $p->colors,
            'sale'             => $p->has_discount && $p->isDiscountActive(),
            'discount_percent' => $p->discount_percent,
            'discount_note'    => $p->discount_note,
            'variants'         => $p->variants,
        ];
    }

   public function getRecommendations(Post $product)
{
    $orderIds = DB::table('order_items')
        ->where('post_id', $product->id)
        ->pluck('order_id');

    $suggestedProducts = collect();

    if ($orderIds->isNotEmpty()) {
        $frequentlyBoughtIds = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->where('post_id', '!=', $product->id)
            ->select('post_id', DB::raw('COUNT(*) as total_bought'))
            ->groupBy('post_id')
            ->orderByDesc('total_bought')
            ->limit(4)
            ->pluck('post_id');

        if ($frequentlyBoughtIds->isNotEmpty()) {
            $suggestedProducts = Post::whereIn('id', $frequentlyBoughtIds)->get();
        }
    }


    if ($suggestedProducts->isEmpty()) {
        $suggestedProducts = Post::where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->take(4)
            ->get();
    }

    return response()->json($suggestedProducts);
}
}
