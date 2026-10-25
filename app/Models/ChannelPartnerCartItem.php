<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPartnerCartItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
