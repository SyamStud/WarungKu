<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Debt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'transaction_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'last_payment_at',
        'settled_at',
    ];

    public function debtItems()
    {
        return $this->hasMany(DebtItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
