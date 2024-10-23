<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DebtPaymentItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'debt_payment_id',
        'debt_item_id',
        'amount',
        'remaining_debt',
        'store_id',
    ];

    public function debtPayment()
    {
        return $this->belongsTo(DebtPayment::class);
    }

    public function debtItem()
    {
        return $this->belongsTo(DebtItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
