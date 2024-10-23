<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'contact_name',
        'contact_phone',
        'contact_email',
        'contact_position',
        'status',
        'store_id',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
