<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class post extends Model
{
    protected $fillable = [
        'id',
        'title',
        'content',
        'status',
        'picture',
        'category',
        'default_price',
        'variants',
        'has_discount',
        'discount_percent',
        'discount_start',
        'discount_end',
        'discount_note',
    ];

    protected $casts = [
        'has_discount' => 'boolean',
        'discount_start' => 'date',
        'discount_end' => 'date',
    ];


    public function variants(){

        return $this->hasMany(ProductVariant::class);

    }

    // قیمت نهایی بعد از تخفیف
    public function getFinalPriceAttribute()
    {
        if ($this->has_discount && $this->discount_percent && $this->isDiscountActive()) {
            return $this->default_price * (1 - $this->discount_percent / 100);
        }
        return $this->default_price;
    }

    public function isDiscountActive(): bool
    {
        $today = now()->toDateString();
        return (!$this->discount_start || $this->discount_start <= $today)
            && (!$this->discount_end   || $this->discount_end   >= $today);
    }

    // لیست سایزهای موجود
    public function getSizesAttribute()
    {
        return $this->variants->pluck('size')->unique()->values();
    }

    // لیست رنگ‌های موجود
    public function getColorsAttribute()
    {
        return $this->variants->pluck('color')->unique()->values();
    }


}
