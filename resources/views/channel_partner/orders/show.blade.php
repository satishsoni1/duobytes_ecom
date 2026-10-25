@extends('channel_partner.layouts.app')

@section('title', translate('Order') . ' #' . $order->code)

@php
    $address = json_decode($order->shipping_address, true) ?: [];
    $flow = ['pending' => translate('Order placed'), 'confirmed' => translate('Confirmed'), 'picked_up' => translate('Dispatched'), 'on_the_way' => translate('On the way'), 'delivered' => translate('Delivered')];
    $keys = array_keys($flow);
    $currentIndex = array_search($order->delivery_status, $keys, true);
    $cancelled = $order->delivery_status === 'cancelled';
    $snapshot = collect(optional($meta)->pricing_snapshot ?? [])->keyBy(fn ($row) => $row['product_id'] . '|' . $row['variation']);
@endphp

@section('content')
    <a href="{{ route('channel_partner.orders.index') }}" class="cp-row cp-small" style="margin-bottom:12px;color:var(--cp-ink-2) !important">
        <x-cp-icon name="arrow-left" class="cp-icon-sm" /> {{ translate('All orders') }}
    </a>

    @if (request('placed'))
        <div class="cp-card cp-status-hero" style="margin-bottom:16px">
            <div class="cp-status-icon" style="background:var(--cp-success-50);color:var(--cp-success)"><x-cp-icon name="check-circle" class="cp-icon-lg" /></div>
            <h1>{{ translate('Order placed!') }}</h1>
            <p class="cp-muted" style="margin-top:6px">{{ translate('We have received your order and will confirm it shortly.') }}</p>
        </div>
    @endif

    <div class="cp-page-head">
        <div>
            <h1>#{{ $order->code }}</h1>
            <p>{{ date('d M Y, h:i A', $order->date) }}@if ($meta && $meta->po_number) · PO {{ $meta->po_number }}@endif</p>
        </div>
        @include('channel_partner.partials.order_status', ['status' => $order->delivery_status])
    </div>

    <div class="cp-cart-layout">
        <div class="cp-stack">
            <section class="cp-card cp-card-pad">
                <h3>{{ translate('Status') }}</h3>
                @if ($cancelled)
                    <div class="cp-alert cp-alert-danger" style="margin-top:12px"><x-cp-icon name="x" class="cp-icon-sm" /><div>{{ translate('This order was cancelled.') }}</div></div>
                @else
                    <ol class="cp-timeline">
                        @foreach ($flow as $key => $label)
                            @php $i = $loop->index; @endphp
                            <li class="{{ $currentIndex !== false && $i < $currentIndex ? 'done' : ($i === $currentIndex ? ($key === 'delivered' ? 'done' : 'current') : '') }}">
                                <span class="dot"></span><strong>{{ $label }}</strong>
                            </li>
                        @endforeach
                    </ol>
                @endif
                @if ($order->tracking_code)
                    <div class="cp-small cp-muted">{{ translate('Tracking code') }}: <strong style="color:var(--cp-ink)">{{ $order->tracking_code }}</strong></div>
                @endif
            </section>

            <section class="cp-card">
                <div class="cp-card-head"><h3>{{ translate('Items') }}</h3></div>
                <ul class="cp-list">
                    @foreach ($order->orderDetails as $detail)
                        @php $snap = $snapshot->get($detail->product_id . '|' . (string) $detail->variation); @endphp
                        <li class="cp-between" style="padding:12px 16px;align-items:flex-start">
                            <div style="min-width:0">
                                <div class="cp-small" style="font-weight:600">{{ $detail->product ? $detail->product->getTranslation('name') : ($snap['name'] ?? translate('Product')) }}</div>
                                <div class="cp-small cp-muted">
                                    {{ $detail->quantity }} × {{ single_price($detail->quantity ? $detail->price / $detail->quantity : 0) }}
                                    @if ($detail->variation) · {{ str_replace('-', ' / ', $detail->variation) }} @endif
                                </div>
                                @if ($snap && $snap['rule'])<div class="cp-small cp-savings">{{ $snap['rule'] }}</div>@endif
                            </div>
                            <strong class="cp-small">{{ single_price($detail->price) }}</strong>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <aside class="cp-stack">
            <section class="cp-card cp-card-pad">
                <h3 style="margin-bottom:10px">{{ translate('Summary') }}</h3>
                <div class="cp-summary">
                    @if ($meta && $meta->discount_total > 0)
                        <div><span class="cp-muted">{{ translate('List price') }}</span><span>{{ single_price($meta->list_total) }}</span></div>
                        <div class="cp-savings"><span>{{ translate('Partner savings') }}</span><span>− {{ single_price($meta->discount_total) }}</span></div>
                    @endif
                    <div><span class="cp-muted">{{ translate('Subtotal') }}</span><span>{{ single_price($order->orderDetails->sum('price')) }}</span></div>
                    <div><span class="cp-muted">{{ translate('Tax') }}</span><span>{{ single_price($order->orderDetails->sum('tax') + $order->orderDetails->sum('gst_amount')) }}</span></div>
                    <div class="total"><span>{{ translate('Total') }}</span><span>{{ single_price($order->grand_total) }}</span></div>
                </div>
                <div class="cp-between cp-small" style="margin-top:12px">
                    <span class="cp-muted">{{ translate('Payment') }}</span>
                    <span class="cp-pill {{ $order->payment_status === 'paid' ? 'cp-pill-success' : 'cp-pill-warning' }}">{{ translate(ucfirst($order->payment_status)) }}</span>
                </div>
            </section>

            <section class="cp-card cp-card-pad">
                <h3 style="margin-bottom:8px">{{ translate('Deliver to') }}</h3>
                <div class="cp-small" style="color:var(--cp-ink-2)">
                    <strong style="color:var(--cp-ink)">{{ $address['name'] ?? '' }}</strong><br>
                    {{ $address['address'] ?? '' }}<br>
                    {{ $address['city'] ?? '' }}, {{ $address['state'] ?? '' }} {{ $address['postal_code'] ?? '' }}<br>
                    {{ $address['phone'] ?? '' }}
                </div>
            </section>

            <div class="cp-grid cp-grid-2">
                <a href="{{ route('channel_partner.orders.invoice', $order->code) }}" class="cp-btn cp-btn-light"><x-cp-icon name="download" class="cp-icon-sm" /> {{ translate('Invoice') }}</a>
                <form method="POST" action="{{ route('channel_partner.orders.reorder', $order->code) }}">
                    @csrf
                    <button type="submit" class="cp-btn cp-btn-light cp-btn-block"><x-cp-icon name="refresh" class="cp-icon-sm" /> {{ translate('Re-order') }}</button>
                </form>
            </div>
        </aside>
    </div>
@endsection
