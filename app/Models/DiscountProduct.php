<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_id',
        'product_variant_id',
        'is_active',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
