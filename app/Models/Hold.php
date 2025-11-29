<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
    protected $fillable = [
        'product_id',
        'expires_at',
        'status',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
