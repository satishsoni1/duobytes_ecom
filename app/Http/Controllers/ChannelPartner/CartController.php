<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Models\ChannelPartnerCartItem;
use Illuminate\Http\Request;

class CartController extends PartnerController
{
    public function index(Request $request)
    {
        $partner = $this->partner($request);

        return view('channel_partner.cart', [
            'partner' => $partner,
            'summary' => $this->cartSummary($partner),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variation' => ['nullable', 'string', 'max:191'],
            'quantity' => ['required', 'integer', 'min:1', 'max:' . config('channel_partner.max_line_quantity')],
            // "set" replaces the line quantity (product page); default adds to it
            'mode' => ['nullable', 'in:add,set'],
        ]);

        $partner = $this->partner($request);
        $product = $this->orderableProducts()->findOrFail($data['product_id']);
        $variation = (string) ($data['variation'] ?? '');

        if (!$product->stocks->where('variant', $variation)->first()) {
            return $this->respond($request, false, translate('Please choose an available option.'), 422);
        }

        $item = ChannelPartnerCartItem::firstOrNew([
            'channel_partner_id' => $partner->id,
            'product_id' => $product->id,
            'variation' => $variation,
        ]);

        $quantity = ($data['mode'] ?? 'add') === 'set' || !$item->exists
            ? (int) $data['quantity']
            : $item->quantity + (int) $data['quantity'];

        $quantity = max($quantity, max(1, (int) $product->min_qty));
        $item->quantity = min($quantity, config('channel_partner.max_line_quantity'));
        $item->save();

        return $this->respond($request, true, translate('Added to cart'));
    }

    public function update(Request $request, ChannelPartnerCartItem $item)
    {
        $partner = $this->partner($request);
        abort_unless($item->channel_partner_id === $partner->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:' . config('channel_partner.max_line_quantity')],
        ]);

        if ((int) $data['quantity'] === 0) {
            $item->delete();
        } else {
            $item->quantity = max((int) $data['quantity'], max(1, (int) optional($item->product)->min_qty));
            $item->save();
        }

        return $this->respond($request, true, translate('Cart updated'));
    }

    public function destroy(Request $request, ChannelPartnerCartItem $item)
    {
        abort_unless($item->channel_partner_id === $this->partner($request)->id, 404);
        $item->delete();

        return $this->respond($request, true, translate('Item removed'));
    }

    public function clear(Request $request)
    {
        $this->partner($request)->cartItems()->delete();

        return $this->respond($request, true, translate('Cart cleared'));
    }

    private function respond(Request $request, bool $ok, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            $partner = $this->partner($request);
            $summary = $this->cartSummary($partner);

            return response()->json([
                'ok' => $ok,
                'message' => $message,
                'cart_count' => count($summary['lines']),
                'totals' => $summary['totals'] + [
                    'net_formatted' => single_price($summary['totals']['net']),
                    'tax_formatted' => single_price($summary['totals']['tax']),
                    'grand_formatted' => single_price($summary['totals']['grand']),
                    'discount_formatted' => single_price($summary['totals']['discount']),
                    'list_formatted' => single_price($summary['totals']['list']),
                ],
                'lines' => collect($summary['lines'])->mapWithKeys(fn ($line) => [
                    $line['item']->id => [
                        'quantity' => $line['item']->quantity,
                        'issue' => $line['issue'],
                        'quote' => $line['quote'] ? CatalogController::formatQuote($line['quote']) : null,
                    ],
                ]),
                'has_issues' => $summary['has_issues'],
                'min_order_shortfall' => $summary['min_order_shortfall'],
                'min_order_shortfall_formatted' => single_price($summary['min_order_shortfall']),
            ], $status);
        }

        flash($message)->{$ok ? 'success' : 'warning'}();
        return back();
    }
}
