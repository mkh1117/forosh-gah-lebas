<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'receiver_name',
        'phone',
        'address',
    ];

    // رابطه با کاربر
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // رابطه با آیتم‌های سفارش
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
