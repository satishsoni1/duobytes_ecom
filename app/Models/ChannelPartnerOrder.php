<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPartnerOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'list_total' => 'float',
        'discount_total' => 'float',
        'net_total' => 'float',
        'pricing_snapshot' => 'array',
    ];

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function combinedOrder()
    {
        return $this->belongsTo(CombinedOrder::class);
    }
}
