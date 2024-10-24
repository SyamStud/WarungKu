<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
        'status',
        'reason_of_rejection',
    ];

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    public function debtItems()
    {
        return $this->hasMany(DebtItem::class);
    }

    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function debtPaymentItems()
    {
        return $this->hasMany(DebtPaymentItem::class);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function discountProducts()
    {
        return $this->hasMany(DiscountProduct::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function restocks()
    {
        return $this->hasMany(Restock::class);
    }

    public function restockLists()
    {
        return $this->hasMany(RestockList::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
