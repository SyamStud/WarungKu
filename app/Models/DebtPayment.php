<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DebtPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_code',
        'customer_id',
        'debt_item_id',
        'amount',
        'paid_at',
        'payment_method',
        'user_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function debtItem()
    {
        return $this->belongsTo(DebtItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
