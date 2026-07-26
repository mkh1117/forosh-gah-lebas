<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ProductVariant extends Model
{
    protected $fillable = [
        'post_id',
        'color',
        'size',
        'price',
        'stock',
        ];

    public function post(){
        return $this->belongsTo(post::class);
    }

    public function getEffectivePriceAttribute(): ?int
    {
        if ($this->price !== null) {
            return (float) $this->price;
        }

        return $this->post->default_price ?? null;
    }

    public function getEffectiveStockAttribute(): int
    {
        if ($this->stock !== null) {
            return (int) $this->stock;
        }
        return (int) ($this->post->default_stock ?? 0);
    }

    public function getEffectiveSizeAttribute()
    {
        return json_encode($this->post->size);
    }

    public function orderItems()
    {
    return $this->hasMany(OrderItem::class);
    }
}
