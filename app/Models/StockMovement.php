<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = ['product_variant_id', 'quantity', 'type', 'reference'];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
