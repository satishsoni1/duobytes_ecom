<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Http\Controllers\InvoiceController;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerCartItem;
use App\Models\ChannelPartnerOrder;
use App\Models\CombinedOrder;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductStock;
use App\Utility\NotificationUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class OrderController extends PartnerController
{
    public function checkout(Request $request)
    {
        $partner = $this->partner($request);
        $summary = $this->cartSummary($partner);

        if (empty($summary['lines'])) {
            return redirect()->route('channel_partner.cart');
        }

        return view('channel_partner.checkout', compact('partner', 'summary'));
    }

    public function place(Request $request)
    {
        $partner = $this->partner($request);

        $data = $request->validate([
            'shipping_name' => ['required', 'string', 'max:100'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_state' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'max:100'],
            'po_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $summary = $this->cartSummary($partner);

        if (empty($summary['lines'])) {
            return redirect()->route('channel_partner.cart');
        }
        if ($summary['has_issues']) {
            flash(translate('Please fix the highlighted items in your cart.'))->warning();
            return redirect()->route('channel_partner.cart');
        }
        if ($summary['min_order_shortfall'] > 0) {
            flash(translate('Your contract minimum order value is') . ' ' . single_price($partner->min_order_value))->warning();
            return redirect()->route('channel_partner.cart');
        }
        if ($summary['credit']['exceeded']) {
            flash(translate('This order exceeds your available credit of') . ' ' . single_price(max(0, $summary['credit']['available'])) . '. ' . translate('Please contact your account manager.'))->error();
            return redirect()->route('channel_partner.checkout');
        }

        $address = [
            'name' => $data['shipping_name'],
            'email' => $request->user()->email,
            'address' => $data['shipping_address'],
            'country' => $data['shipping_country'] ?: ($partner->country ?: ''),
            'state' => $data['shipping_state'],
            'city' => $data['shipping_city'],
            'postal_code' => $data['shipping_postal_code'],
            'phone' => $data['shipping_phone'],
        ];
        $billing = [
            'name' => $partner->company_name,
            'email' => $request->user()->email,
            'address' => $partner->address_line,
            'country' => $partner->country ?: '',
            'state' => $partner->state,
            'city' => $partner->city,
            'postal_code' => $partner->postal_code,
            'phone' => $request->user()->phone,
            'gst_number' => $partner->gst_number,
        ];

        try {
            $combinedOrder = DB::transaction(fn () => $this->createOrders($partner, $request->user()->id, $address, $billing, $data));
        } catch (RuntimeException $e) {
            flash($e->getMessage())->warning();
            return redirect()->route('channel_partner.cart');
        }

        foreach ($combinedOrder->orders as $order) {
            try {
                NotificationUtility::sendOrderPlacedNotification($order);
            } catch (\Throwable $e) {
                Log::warning('Partner order notification failed for ' . $order->code . ': ' . $e->getMessage());
            }
        }

        $first = $combinedOrder->orders->first();
        flash(translate('Order placed successfully'))->success();

        return redirect()->route('channel_partner.orders.show', ['code' => $first->code, 'placed' => 1]);
    }

    public function index(Request $request)
    {
        $partner = $this->partner($request);

        $orders = $this->partnerOrders($partner)
            ->when($request->query('status'), fn ($q, $status) => $q->where('delivery_status', $status))
            ->when($request->query('q'), fn ($q, $term) => $q->where('code', 'like', '%' . $term . '%'))
            ->withCount('orderDetails')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $poNumbers = ChannelPartnerOrder::whereIn('order_id', $orders->pluck('id'))->pluck('po_number', 'order_id');

        return view('channel_partner.orders.index', compact('partner', 'orders', 'poNumbers'));
    }

    public function show(Request $request, string $code)
    {
        $partner = $this->partner($request);
        $order = $this->partnerOrders($partner)->where('code', $code)->with('orderDetails.product')->firstOrFail();
        $meta = ChannelPartnerOrder::where('order_id', $order->id)->first();

        return view('channel_partner.orders.show', compact('partner', 'order', 'meta'));
    }

    public function invoice(Request $request, string $code)
    {
        $order = $this->partnerOrders($this->partner($request))->where('code', $code)->firstOrFail();

        return (new InvoiceController)->invoice_download($order->id);
    }

    public function reorder(Request $request, string $code)
    {
        $partner = $this->partner($request);
        $order = $this->partnerOrders($partner)->where('code', $code)->with('orderDetails')->firstOrFail();

        $orderableIds = $this->orderableProducts()->whereIn('id', $order->orderDetails->pluck('product_id'))->pluck('id')->all();
        $added = 0;

        foreach ($order->orderDetails as $detail) {
            if (!in_array($detail->product_id, $orderableIds)) {
                continue;
            }
            $item = ChannelPartnerCartItem::firstOrNew([
                'channel_partner_id' => $partner->id,
                'product_id' => $detail->product_id,
                'variation' => (string) $detail->variation,
            ]);
            $item->quantity = ($item->exists ? $item->quantity : 0) + $detail->quantity;
            $item->save();
            $added++;
        }

        flash($added
            ? translate('Items from order') . ' ' . $order->code . ' ' . translate('were added to your cart')
            : translate('None of these products are available right now'))->{$added ? 'success' : 'warning'}();

        return redirect()->route('channel_partner.cart');
    }

    private function partnerOrders(ChannelPartner $partner)
    {
        return Order::where('user_id', $partner->user_id)->where('order_from', config('channel_partner.order_from'));
    }

    /**
     * Creates one order per product owner (same split as the storefront checkout)
     * inside the caller's transaction. Prices are re-quoted under row locks.
     */
    private function createOrders(ChannelPartner $partner, int $userId, array $address, array $billing, array $data): CombinedOrder
    {
        $items = ChannelPartnerCartItem::where('channel_partner_id', $partner->id)->lockForUpdate()->get();
        if ($items->isEmpty()) {
            throw new RuntimeException(translate('Your cart is empty'));
        }

        $products = Product::with(['stocks', 'taxes', 'categories:id', 'product_translations'])
            ->whereIn('id', $items->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $stocks = ProductStock::whereIn('product_id', $items->pluck('product_id'))->lockForUpdate()->get();

        $gstActive = addon_is_activated('gst_system');
        $notes = trim(($data['po_number'] ? 'PO: ' . $data['po_number'] . "\n" : '') . ($data['notes'] ?? ''));

        $combinedOrder = new CombinedOrder;
        $combinedOrder->user_id = $userId;
        $combinedOrder->shipping_address = json_encode($address);
        $combinedOrder->grand_total = 0;
        $combinedOrder->save();

        $bySeller = $items->groupBy(fn ($item) => optional($products->get($item->product_id))->user_id);

        foreach ($bySeller as $sellerId => $sellerItems) {
            $order = new Order;
            $order->combined_order_id = $combinedOrder->id;
            $order->user_id = $userId;
            $order->seller_id = $sellerId;
            $order->shipping_address = $combinedOrder->shipping_address;
            $order->billing_address = json_encode($billing);
            $order->additional_info = $notes ?: null;
            $order->shipping_type = 'home_delivery';
            $order->order_from = config('channel_partner.order_from');
            $order->payment_type = config('channel_partner.payment_type');
            $order->payment_status = 'unpaid';
            $order->delivery_status = 'pending';
            $order->delivery_viewed = '0';
            $order->payment_status_viewed = '0';
            do {
                $code = date('Ymd-His') . rand(10, 99);
            } while (Order::where('code', $code)->exists());
            $order->code = $code;
            $order->date = strtotime('now');
            do {
                $tracking = 'TRK-' . strtoupper(Str::random(6)) . substr(time(), -5);
            } while (Order::where('tracking_code', $tracking)->exists());
            $order->tracking_code = $tracking;
            $order->grand_total = 0;
            $order->save();

            $totals = ['list' => 0, 'net' => 0, 'tax' => 0, 'gst' => 0, 'discount' => 0];
            $snapshot = [];

            foreach ($sellerItems as $item) {
                $product = $products->get($item->product_id);
                $stock = $stocks->first(fn ($s) => $s->product_id == $item->product_id && (string) $s->variant === (string) $item->variation);

                if (!$product || !$stock) {
                    throw new RuntimeException(translate('An item in your cart is no longer available.'));
                }
                if ($stock->qty < $item->quantity) {
                    throw new RuntimeException(translate('The requested quantity is not available for') . ' ' . $product->getTranslation('name'));
                }

                $quote = $this->pricing->quote($partner, $product, $item->variation, $item->quantity);

                $detail = new OrderDetail;
                $detail->order_id = $order->id;
                $detail->seller_id = $product->user_id;
                $detail->product_id = $product->id;
                $detail->variation = $item->variation;
                $detail->price = $quote['line_net'];
                $detail->tax = $quote['line_tax'];
                $detail->shipping_cost = 0;
                $detail->coupon_discount = 0;
                $detail->quantity = $item->quantity;
                $detail->payment_status = 'unpaid';
                $detail->delivery_status = 'pending';
                $detail->shipping_type = 'home_delivery';
                if ($gstActive) {
                    $detail->gst_rate = $product->gst_rate;
                    $detail->gst_amount = round((($detail->price + $detail->tax) * $product->gst_rate) / 100, 2);
                    $totals['gst'] += $detail->gst_amount;
                }
                $detail->save();

                $stock->qty -= $item->quantity;
                $stock->save();
                $product->num_of_sale += $item->quantity;
                $product->save();

                $totals['list'] += $quote['line_list'];
                $totals['net'] += $quote['line_net'];
                $totals['tax'] += $quote['line_tax'];
                $totals['discount'] += $quote['line_discount'];

                $snapshot[] = [
                    'product_id' => $product->id,
                    'name' => $product->getTranslation('name'),
                    'variation' => $item->variation,
                    'quantity' => $item->quantity,
                    'list_price' => $quote['list_price'],
                    'unit_price' => $quote['unit_price'],
                    'tax_per_unit' => $quote['tax_per_unit'],
                    'rule_id' => $quote['rule_id'],
                    'rule' => $quote['rule_label'],
                ];
            }

            $order->grand_total = round($totals['net'] + $totals['tax'] + $totals['gst'], 2);
            $this->assignInvoiceNumber($order);
            $order->save();

            ChannelPartnerOrder::create([
                'channel_partner_id' => $partner->id,
                'combined_order_id' => $combinedOrder->id,
                'order_id' => $order->id,
                'po_number' => $data['po_number'] ?? null,
                'list_total' => round($totals['list'], 2),
                'discount_total' => round($totals['discount'], 2),
                'net_total' => round($totals['net'], 2),
                'pricing_snapshot' => $snapshot,
            ]);

            $combinedOrder->grand_total += $order->grand_total;
        }

        $combinedOrder->save();
        ChannelPartnerCartItem::where('channel_partner_id', $partner->id)->delete();

        return $combinedOrder->load('orders');
    }

    // Same numbering as the storefront checkout (OrderController::store).
    private function assignInvoiceNumber(Order $order): void
    {
        $config = json_decode(get_setting('invoice_config'), true);
        if (empty($config['generate_invoice_number'])) {
            return;
        }

        $prefix = 'INV-' . date('m') . date('y');
        $last = Order::where('invoice_number', 'like', $prefix . '%')->orderBy('id', 'desc')->lockForUpdate()->first();
        $serial = $last && $last->invoice_number ? ((int) substr($last->invoice_number, -6)) + 1 : 1;

        $order->invoice_number = $prefix . str_pad((string) $serial, 6, '0', STR_PAD_LEFT);
    }
}
