<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartner;
use App\Models\Product;
use App\Services\ChannelPartnerPricingService;
use Illuminate\Http\Request;

abstract class PartnerController extends Controller
{
    public function __construct(protected ChannelPartnerPricingService $pricing)
    {
    }

    protected function partner(Request $request): ChannelPartner
    {
        return $request->attributes->get('channel_partner');
    }

    /**
     * Products a partner may order: published, approved, physical, non-auction.
     */
    protected function orderableProducts()
    {
        return filter_products(Product::query())
            ->where('digital', 0)
            ->with(['stocks', 'taxes', 'thumbnail', 'categories:id', 'product_translations']);
    }

    /**
     * Cart lines with a fresh quote each, plus order totals.
     */
    protected function cartSummary(ChannelPartner $partner): array
    {
        $items = $partner->cartItems()
            ->with(['product.stocks', 'product.taxes', 'product.thumbnail', 'product.categories:id', 'product.product_translations'])
            ->orderBy('id')
            ->get();

        $orderableIds = $this->orderableProducts()->whereIn('id', $items->pluck('product_id'))->pluck('id')->all();

        $lines = [];
        $totals = ['list' => 0, 'net' => 0, 'tax' => 0, 'discount' => 0, 'units' => 0];
        $hasIssues = false;

        foreach ($items as $item) {
            $product = $item->product;
            $issue = null;
            $quote = null;

            if (!$product || !in_array($item->product_id, $orderableIds)) {
                $issue = translate('No longer available');
            } else {
                $quote = $this->pricing->quote($partner, $product, $item->variation, $item->quantity);
                if (!$quote['available']) {
                    $issue = translate('This option is no longer available');
                } elseif ($item->quantity > $quote['stock']) {
                    $issue = $quote['stock'] > 0
                        ? translate('Only') . ' ' . $quote['stock'] . ' ' . translate('in stock')
                        : translate('Out of stock');
                } elseif ($item->quantity < max(1, (int) $product->min_qty)) {
                    $issue = translate('Minimum order quantity is') . ' ' . $product->min_qty;
                }
            }

            if ($issue === null) {
                $totals['list'] += $quote['line_list'];
                $totals['net'] += $quote['line_net'];
                $totals['tax'] += $quote['line_tax'];
                $totals['discount'] += $quote['line_discount'];
                $totals['units'] += $item->quantity;
            } else {
                $hasIssues = true;
            }

            $lines[] = ['item' => $item, 'product' => $product, 'quote' => $quote, 'issue' => $issue];
        }

        $totals = array_map(fn ($v) => round($v, 2), $totals);
        $totals['grand'] = round($totals['net'] + $totals['tax'], 2);

        $minOrderShortfall = $partner->min_order_value > 0 && $totals['net'] < $partner->min_order_value
            ? round($partner->min_order_value - $totals['net'], 2)
            : 0;

        return [
            'lines' => $lines,
            'totals' => $totals,
            'has_issues' => $hasIssues,
            'min_order_shortfall' => $minOrderShortfall,
        ];
    }

    protected function cartCount(ChannelPartner $partner): int
    {
        return (int) $partner->cartItems()->count();
    }
}
