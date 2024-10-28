<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_id',
        'key',
        'value',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
