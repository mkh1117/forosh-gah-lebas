<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Post;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/products', function (Request $request) {
    $query = Post::with('variants');

    // فیلتر دسته‌بندی
    if ($request->category) {
        $query->where('category', $request->category);
    }

    // فیلتر سایز
    if ($request->sizes) {
        $sizes = explode(',', $request->sizes);
        $query->whereHas('variants', fn($q) => $q->whereIn('size', $sizes)->where('stock', '>', 0));
    }

    // فیلتر رنگ
    if ($request->colors) {
        $colors = explode(',', $request->colors);
        $query->whereHas('variants', fn($q) => $q->whereIn('color', $colors)->where('stock', '>', 0));
    }

    // فقط حراجی‌ها
   if ($request->sale_only == '1' || $request->sale_only === 'true'){
        $today = now()->toDateString();
        $query->where('has_discount', true)
              ->where(fn($q) => $q->whereNull('discount_start')->orWhere('discount_start', '<=', $today))
              ->where(fn($q) => $q->whereNull('discount_end')->orWhere('discount_end', '>=', $today));
    }

    // فیلتر قیمت
    if ($request->filled('min_price')) {
        $query->where('default_price', '>=', $request->min_price);
    }
    if ($request->filled('max_price')) {
        $query->where('default_price', '<=', $request->max_price);
    }

    $posts = $query->get();

    return response()->json($posts->map(function ($post) {
        $today    = now()->toDateString();
        $saleActive = $post->has_discount
            && $post->discount_percent
            && (!$post->discount_start || $post->discount_start <= $today)
            && (!$post->discount_end   || $post->discount_end   >= $today);

        return [
            'id'               => $post->id,
            'title'            => $post->title,
            'price'            => $post->default_price,
            'picture'          => str_replace('posts/', '', $post->picture),
            'category'         => $post->category,
            'sale'             => $saleActive,
            'discount_percent' => $saleActive ? $post->discount_percent : null,
            'sizes'            => $post->variants->pluck('size')->unique()->values(),
            'colors'           => $post->variants->pluck('color')->unique()->values(),
        ];
    }));
});

Route::get('/products/{productId}', function ($productId) {
    $post = Post::with('variants')->where('id', $productId)->first();

    if (!$post) {
        return response()->json(['message' => 'not found'], 404);
    }

    $addressPicture = str_replace("posts/", "", $post->picture);

    // سایزها و رنگ‌های unique از variants
    $sizes  = $post->variants->pluck('size')->unique()->values();
    $colors = $post->variants->pluck('color')->unique()->values();

    return response()->json([
        'id'               => $post->id,
        'title'            => $post->title,
        'price'            => $post->default_price,
        'content'          => $post->content,
        'picture'          => $addressPicture,
        'category'         => $post->category,
        'has_discount'     => $post->has_discount,
        'discount_percent' => $post->discount_percent,
        'discount_start'   => $post->discount_start,
        'discount_end'     => $post->discount_end,
        'discount_note'    => $post->discount_note,
        'sizes'            => $sizes,
        'colors'           => $colors,
        'variants'         => $post->variants, // برای چک موجودی بعداً
    ]);
});



Route::get('/off', function () {
    $offs = post::where('has_discount',true)->orderBy('discount_percent','desc')->limit(4)->get();
    if ($offs->isEmpty()) {
        return response()->json(['message' => 'not found'], 404);
    }
   $responseData = [];
    foreach ($offs as $off) {
        $addressPicture = str_replace('posts/', '', $off->picture);

        $responseData[] = [
            'id'        => $off->id,
            'title'     => $off->title,
            'price'     => $off->default_price,
            'content'   => $off->content,
            'status'    => $off->status,
            'picture'   => $addressPicture,
            'category'  => $off->category,
        ];
    }

    return response()->json($responseData);

});
