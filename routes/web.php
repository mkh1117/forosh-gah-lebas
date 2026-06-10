<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return "welcome";
});

Route::get('/picture/{address}', function ($address) {


    if (!Storage::disk('local')->exists('posts/'. $address)) {
        dd($address);
         abort(404, "File not found.");
    }

    // 3. محتوای فایل را بخوانید
    $fileContents = Storage::disk('local')->get('posts/'. $address);
    $mimeType = File::mimeType(storage_path('app/' . 'private/'.'posts/'. $address));

    // 4. پاسخ HTTP مناسب برای بازگرداندن فایل
    return response($fileContents)
        ->header('Content-Type', $mimeType);
    })->name('private.file');
