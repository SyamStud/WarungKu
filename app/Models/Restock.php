<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'quantity',
        'difference',
        'cost',
        'status',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
