<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'transaction_item_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'last_payment_at',
        'settled_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactionItem()
    {
        return $this->belongsTo(TransactionItem::class);
    }
}
