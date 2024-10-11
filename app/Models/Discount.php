<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'amount',
        'amount_type',
        'threshold',
        'start_date',
        'end_date',
        'is_active',
    ];

    public function discountProducts()
    {
        return $this->hasMany(DiscountProduct::class);
    }
}
