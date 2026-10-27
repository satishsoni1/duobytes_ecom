<?php

namespace App\Services;

use App\Models\ChannelPartner;
use App\Models\Order;

/**
 * Builds the data for a GST Tax Invoice (rule 46, CGST Rules 2017) from an
 * order placed by a channel partner or a direct customer. The view
 * `invoices.gst_tax_invoice` only renders what this returns.
 */
class GstInvoiceBuilder
{
    public function fromOrder(Order $order): array
    {
        $order->loadMissing('orderDetails.product');

        $business = json_decode(get_setting('business_info'), true) ?? [];
        $invoiceConfig = json_decode(get_setting('invoice_config'), true) ?? [];
        $shipping = json_decode($order->shipping_address, true) ?? [];
        $billing = json_decode($order->billing_address, true) ?: $shipping;

        $seller = [
            'name' => $business['company_name'] ?? get_setting('website_name'),
            'address' => $business['address'] ?? get_setting('contact_address'),
            'state' => $business['state'] ?? '',
            'postal_code' => $business['postal_code'] ?? '',
            'gstin' => get_seller_gstin($order),
            'phone' => $business['phone'] ?? get_setting('contact_phone'),
            'email' => $business['email'] ?? get_setting('contact_email'),
        ];

        $isPartner = $order->order_from === config('channel_partner.order_from');
        $partner = $isPartner ? ChannelPartner::where('user_id', $order->user_id)->first() : null;

        $buyer = [
            'type' => $isPartner ? 'Channel Partner' : 'Customer',
            'code' => $partner?->partner_code,
            'name' => $billing['name'] ?? '',
            'address' => $billing['address'] ?? '',
            'city' => $billing['city'] ?? '',
            'state' => $billing['state'] ?? '',
            'postal_code' => $billing['postal_code'] ?? '',
            'phone' => $billing['phone'] ?? '',
            'gstin' => $billing['gst_number'] ?? $partner?->gst_number,
            'pan' => $partner?->pan_number,
        ];

        $shipTo = [
            'name' => $shipping['name'] ?? '',
            'address' => $shipping['address'] ?? '',
            'city' => $shipping['city'] ?? '',
            'state' => $shipping['state'] ?? '',
            'postal_code' => $shipping['postal_code'] ?? '',
            'phone' => $shipping['phone'] ?? '',
        ];

        $lines = [];
        foreach ($order->orderDetails as $detail) {
            $product = $detail->product;
            $taxable = (float) $detail->price - (float) $detail->coupon_discount;
            $lines[] = [
                'description' => $product ? $product->getTranslation('name') : translate('Product'),
                'variation' => $detail->variation,
                'hsn' => $product->hsn_code ?? '',
                'quantity' => (int) $detail->quantity,
                'unit' => $product->unit ?? 'Nos',
                'rate' => $detail->quantity ? $taxable / $detail->quantity : 0,
                'taxable' => $taxable,
                'gst_rate' => (float) ($detail->gst_rate ?? 0),
            ];
            if ((float) $detail->shipping_cost > 0) {
                $lines[] = [
                    'description' => translate('Freight / shipping'),
                    'variation' => null,
                    'hsn' => '996511',
                    'quantity' => 1,
                    'unit' => 'Svc',
                    'rate' => (float) $detail->shipping_cost,
                    'taxable' => (float) $detail->shipping_cost,
                    'gst_rate' => (float) ($detail->gst_rate ?? 0),
                ];
            }
        }

        return $this->assemble([
            'title' => 'Tax Invoice',
            'number' => $order->invoice_number ?: $order->code,
            'date' => date('d-m-Y', $order->date),
            'order_code' => $order->code,
            'po_number' => $isPartner ? optional(\App\Models\ChannelPartnerOrder::where('order_id', $order->id)->first())->po_number : null,
            'payment' => ucwords(str_replace('_', ' ', (string) $order->payment_type)),
            'payment_status' => ucfirst((string) $order->payment_status),
            'reverse_charge' => 'No',
            'seller' => $seller,
            'buyer' => $buyer,
            'ship_to' => $shipTo,
            'lines' => $lines,
            'bank' => $invoiceConfig['bank'] ?? null,
            'footer' => $invoiceConfig['footer_text'] ?? null,
        ]);
    }

    /**
     * Fixed sample data, used to preview the layout without a real order.
     */
    public function sample(string $type = 'partner'): array
    {
        $partner = $type !== 'customer';

        return $this->assemble([
            'title' => 'Tax Invoice',
            'number' => 'INV-1026000123',
            'date' => '27-10-2026',
            'order_code' => '20261027-11452318',
            'po_number' => $partner ? 'PO/RK/2026/045' : null,
            'payment' => $partner ? 'Advance Payment' : 'Razorpay (UPI)',
            'payment_status' => 'Paid',
            'reverse_charge' => 'No',
            'seller' => [
                'name' => 'Toyeza Retail Private Limited',
                'address' => 'Unit 204, Andheri Industrial Estate, Off Veera Desai Road, Andheri (West), Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400053',
                'gstin' => '27AABCT1234F1Z5',
                'phone' => '+91 22 4000 1234',
                'email' => 'accounts@toyeza.com',
            ],
            'buyer' => $partner ? [
                'type' => 'Channel Partner',
                'code' => 'CP00012',
                'name' => 'Rahul Kumar',
                'address' => '14, Station Road, Near Bus Stand',
                'city' => 'Nashik',
                'state' => 'Maharashtra',
                'postal_code' => '422001',
                'phone' => '+91 98765 43210',
                'gstin' => null,
                'pan' => 'ABCPK1234L',
            ] : [
                'type' => 'Customer',
                'code' => null,
                'name' => 'Priya Sharma',
                'address' => 'B-702, Lake View Apartments, HSR Layout',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560102',
                'phone' => '+91 99887 76655',
                'gstin' => null,
                'pan' => null,
            ],
            'ship_to' => $partner ? [
                'name' => 'Rahul Kumar',
                'address' => '14, Station Road, Near Bus Stand',
                'city' => 'Nashik',
                'state' => 'Maharashtra',
                'postal_code' => '422001',
                'phone' => '+91 98765 43210',
            ] : [
                'name' => 'Priya Sharma',
                'address' => 'B-702, Lake View Apartments, HSR Layout',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560102',
                'phone' => '+91 99887 76655',
            ],
            'lines' => $partner ? [
                ['description' => 'Wooden Stacking Blocks (30 pcs)', 'variation' => null, 'hsn' => '95030090', 'quantity' => 24, 'unit' => 'Pcs', 'rate' => 349.00, 'taxable' => 8376.00, 'gst_rate' => 12],
                ['description' => 'RC Racing Car 1:24', 'variation' => 'Red', 'hsn' => '95030030', 'quantity' => 12, 'unit' => 'Pcs', 'rate' => 899.00, 'taxable' => 10788.00, 'gst_rate' => 12],
                ['description' => 'Soft Plush Teddy Bear 40 cm', 'variation' => 'Brown', 'hsn' => '95030021', 'quantity' => 20, 'unit' => 'Pcs', 'rate' => 425.00, 'taxable' => 8500.00, 'gst_rate' => 12],
                ['description' => 'Kids Art & Craft Kit', 'variation' => null, 'hsn' => '95030090', 'quantity' => 15, 'unit' => 'Pcs', 'rate' => 279.00, 'taxable' => 4185.00, 'gst_rate' => 12],
                ['description' => 'Board Game - Business Tycoon', 'variation' => null, 'hsn' => '95049090', 'quantity' => 10, 'unit' => 'Pcs', 'rate' => 520.00, 'taxable' => 5200.00, 'gst_rate' => 18],
            ] : [
                ['description' => 'RC Racing Car 1:24', 'variation' => 'Blue', 'hsn' => '95030030', 'quantity' => 1, 'unit' => 'Pcs', 'rate' => 1199.00, 'taxable' => 1199.00, 'gst_rate' => 12],
                ['description' => 'Board Game - Business Tycoon', 'variation' => null, 'hsn' => '95049090', 'quantity' => 1, 'unit' => 'Pcs', 'rate' => 699.00, 'taxable' => 699.00, 'gst_rate' => 18],
                ['description' => 'Freight / shipping', 'variation' => null, 'hsn' => '996511', 'quantity' => 1, 'unit' => 'Svc', 'rate' => 60.00, 'taxable' => 60.00, 'gst_rate' => 18],
            ],
            'bank' => [
                'name' => 'HDFC Bank',
                'account' => '50200012345678',
                'ifsc' => 'HDFC0000123',
                'branch' => 'Andheri West, Mumbai',
            ],
            'footer' => 'Goods once sold will be exchanged only as per our returns policy. Subject to Mumbai jurisdiction.',
        ]);
    }

    /**
     * Adds per-line tax, HSN summary, totals and amount in words.
     * CGST + SGST when seller and place of supply are in the same state, else IGST.
     */
    private function assemble(array $invoice): array
    {
        $placeOfSupply = $invoice['ship_to']['state'] ?: $invoice['buyer']['state'];
        $intraState = $this->sameState($invoice['seller']['state'], $placeOfSupply);

        $totals = ['taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'quantity' => 0];
        $hsn = [];

        foreach ($invoice['lines'] as $i => $line) {
            $tax = round($line['taxable'] * $line['gst_rate'] / 100, 2);
            $line['cgst'] = $intraState ? round($tax / 2, 2) : 0;
            $line['sgst'] = $intraState ? round($tax - $line['cgst'], 2) : 0;
            $line['igst'] = $intraState ? 0 : $tax;
            $line['total'] = round($line['taxable'] + $tax, 2);
            $invoice['lines'][$i] = $line;

            $totals['taxable'] += $line['taxable'];
            $totals['cgst'] += $line['cgst'];
            $totals['sgst'] += $line['sgst'];
            $totals['igst'] += $line['igst'];
            $totals['quantity'] += $line['quantity'];

            $key = $line['hsn'] . '|' . $line['gst_rate'];
            $hsn[$key] ??= ['hsn' => $line['hsn'], 'rate' => $line['gst_rate'], 'taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0];
            foreach (['taxable', 'cgst', 'sgst', 'igst'] as $field) {
                $hsn[$key][$field] += $line[$field];
            }
        }

        $totals = array_map(fn ($v) => round($v, 2), $totals);
        $totals['tax'] = round($totals['cgst'] + $totals['sgst'] + $totals['igst'], 2);
        $exact = $totals['taxable'] + $totals['tax'];
        $totals['grand'] = round($exact);
        $totals['round_off'] = round($totals['grand'] - $exact, 2);

        $invoice['place_of_supply'] = $placeOfSupply;
        $invoice['intra_state'] = $intraState;
        $invoice['hsn_summary'] = array_values($hsn);
        $invoice['totals'] = $totals;
        $invoice['amount_in_words'] = $this->rupeesInWords($totals['grand']);
        $invoice['tax_in_words'] = $this->rupeesInWords($totals['tax']);

        return $invoice;
    }

    private function sameState(?string $a, ?string $b): bool
    {
        $norm = fn ($s) => strtolower(preg_replace('/[^a-z]/i', '', (string) $s));
        return $norm($a) !== '' && $norm($a) === $norm($b);
    }

    /**
     * Indian numbering (lakh / crore), e.g. 43,958 -> "Forty Three Thousand Nine Hundred Fifty Eight".
     */
    public function rupeesInWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = $rupees ? $this->toWords($rupees) : 'Zero';
        $text = 'Rupees ' . $words;
        if ($paise) {
            $text .= ' and ' . $this->toWords($paise) . ' Paise';
        }

        return $text . ' Only';
    }

    private function toWords(int $n): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $twoDigits = fn ($x) => $x < 20 ? $ones[$x] : trim($tens[intdiv($x, 10)] . ' ' . $ones[$x % 10]);

        $parts = [];
        foreach ([['Crore', 10000000], ['Lakh', 100000], ['Thousand', 1000]] as [$label, $size]) {
            if ($n >= $size) {
                $parts[] = $this->toWords(intdiv($n, $size)) . ' ' . $label;
                $n %= $size;
            }
        }
        if ($n >= 100) {
            $parts[] = $ones[intdiv($n, 100)] . ' Hundred';
            $n %= 100;
        }
        if ($n > 0) {
            $parts[] = $twoDigits($n);
        }

        return implode(' ', $parts);
    }
}
