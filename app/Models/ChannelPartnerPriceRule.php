<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPartnerPriceRule extends Model
{
    public const TYPES = [
        'percent' => '% off list price',
        'amount' => 'Flat amount off per unit',
        'fixed_price' => 'Fixed unit price',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'discount_value' => 'float',
        'min_qty' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeCurrentlyValid($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    public function targetLabel(): string
    {
        if ($this->product_id) {
            return $this->product ? $this->product->getTranslation('name') : translate('Deleted product');
        }
        if ($this->category_id) {
            return translate('Category') . ': ' . ($this->category ? $this->category->getTranslation('name') : '-');
        }
        return translate('All products');
    }

    public function describe(): string
    {
        $value = $this->discount_type === 'percent'
            ? rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.') . '% off'
            : ($this->discount_type === 'amount'
                ? single_price($this->discount_value) . ' off / unit'
                : single_price($this->discount_value) . ' / unit');

        return $this->min_qty > 1 ? $value . ' (' . $this->min_qty . '+ units)' : $value;
    }

    /**
     * Label shown to the partner next to a price (avoids repeating the price itself).
     */
    public function partnerLabel(): string
    {
        if ($this->discount_type !== 'fixed_price') {
            return $this->describe();
        }
        $label = translate('Contract price');

        return $this->min_qty > 1 ? $label . ' (' . $this->min_qty . '+ ' . translate('units') . ')' : $label;
    }
}
