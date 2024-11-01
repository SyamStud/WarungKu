<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'debt_id',
        'transaction_item_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'last_payment_at',
        'settled_at',
        'store_id',
    ];

    public function debt(){
        return $this->belongsTo(Debt::class)->withTrashed();
    }

    public function transactionItem()
    {
        return $this->belongsTo(TransactionItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
