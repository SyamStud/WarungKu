<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'disount',
        'discounted_price',
        'total_price',
        'discounted_total_price',
        'restock_id',
        'profit',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function debtItems()
    {
        return $this->hasMany(DebtItem::class);
    }
}
