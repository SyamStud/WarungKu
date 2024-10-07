<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtPaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_payment_id',
        'debt_item_id',
        'amount',
        'remaining_debt',
    ];

    public function debtPayment()
    {
        return $this->belongsTo(DebtPayment::class);
    }

    public function debtItem()
    {
        return $this->belongsTo(DebtItem::class);
    }
}
