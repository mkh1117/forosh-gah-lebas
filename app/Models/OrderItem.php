<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'post_id',
        'product_variant_id',
        'color',
        'size',
        'price',
        'quantity',
    ];

    // رابطه با سفارش اصلی
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // رابطه با محصول اصلی (Post)
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    // رابطه با تنوع محصول (ProductVariant)
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
