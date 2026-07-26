<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Post;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// ثبت‌نام
Route::post('/register', function (Request $request) {
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        // is_admin اینجا نیست — default false می‌مونه
    ]);

    $token = $user->createToken('api-token')->plainTextToken;
    return response()->json(['user' => $user, 'token' => $token], 201);
});

// ورود
Route::post('/login', function (Request $request) {
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'message' => 'ایمیل یا رمز عبور اشتباه است',
        ], 401);
    }

    $user  = Auth::user();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user'  => $user,
        'token' => $token,
    ]);
});

// خروج
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'خروج موفق']);
});

// اطلاعات کاربر لاگین‌شده
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return response()->json($request->user());
});

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

Route::get('/products/{product}/recommendations', [ProductController::class, 'getRecommendations']);

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);

