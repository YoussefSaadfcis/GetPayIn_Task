<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhock extends Model
{
    protected $fillable = [
        'idempotency_key',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
