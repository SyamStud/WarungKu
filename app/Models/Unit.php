<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'store_id'];

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
