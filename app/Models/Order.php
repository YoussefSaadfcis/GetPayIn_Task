<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'total_amount',
        'status',
        'hold_id',
    ];

    public function hold()
    {
        return $this->belongsTo(Hold::class);
    }
}
