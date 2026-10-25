@extends('channel_partner.layouts.app')

@section('title', translate('Cart'))

@php
    $t = $summary['totals'];
    $blocked = $summary['has_issues'] || $summary['min_order_shortfall'] > 0 || empty($summary['lines']);
@endphp

@section('content')
    <div class="cp-page-head">
        <div>
            <h1>{{ translate('Cart') }}</h1>
            <p>{{ count($summary['lines']) }} {{ translate('items') }} · {{ $t['units'] }} {{ translate('units') }}</p>
        </div>
        @if (!empty($summary['lines']))
            <form method="POST" action="{{ route('channel_partner.cart.clear') }}" onsubmit="return confirm('{{ translate('Remove all items from the cart?') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="cp-btn cp-btn-ghost cp-btn-sm">{{ translate('Clear cart') }}</button>
            </form>
        @endif
    </div>

    @if (empty($summary['lines']))
        <div class="cp-card cp-empty">
            <x-cp-icon name="cart" class="cp-icon-lg" />
            <h3 style="color:var(--cp-ink)">{{ translate('Your cart is empty') }}</h3>
            <p style="margin-top:6px">{{ translate('Browse the catalogue to add products at your contract price.') }}</p>
            <a href="{{ route('channel_partner.catalog') }}" class="cp-btn cp-btn-primary">{{ translate('Browse catalogue') }}</a>
        </div>
    @else
        <div class="cp-cart-layout">
            <div class="cp-stack">
                <div class="cp-alert cp-alert-warning" data-cart-min @if ($summary['min_order_shortfall'] <= 0) style="display:none" @endif>
                    <x-cp-icon name="info" class="cp-icon-sm" />
                    <div>{{ translate('Add') }} <strong data-cart-min-amount>{{ single_price($summary['min_order_shortfall']) }}</strong> {{ translate('more to reach your contract minimum order of') }} {{ single_price($partner->min_order_value) }}.</div>
                </div>

                <div class="cp-card">
                    @foreach ($summary['lines'] as $line)
                        @php
                            $item = $line['item'];
                            $product = $line['product'];
                            $q = $line['quote'];
                        @endphp
                        <div class="cp-line {{ $line['issue'] ? 'has-issue' : '' }}" data-cart-line="{{ $item->id }}" data-update-url="{{ route('channel_partner.cart.update', $item) }}">
                            <img class="cp-line-img" src="{{ $product ? (uploaded_asset($product->thumbnail_img) ?: static_asset('assets/img/placeholder.jpg')) : static_asset('assets/img/placeholder.jpg') }}" alt="" loading="lazy"
                                 onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}'">
                            <div style="min-width:0">
                                <div class="cp-between" style="align-items:flex-start">
                                    @if ($product)
                                        <a href="{{ route('channel_partner.catalog.show', $product->slug) }}" class="cp-line-name">{{ $product->getTranslation('name') }}</a>
                                    @else
                                        <span class="cp-line-name">{{ translate('Unavailable product') }}</span>
                                    @endif
                                    <button type="button" class="cp-icon-btn" data-cart-remove aria-label="{{ translate('Remove') }}" style="width:34px;height:34px;margin:-6px -6px 0 0"><x-cp-icon name="x" class="cp-icon-sm" /></button>
                                </div>
                                @if ($item->variation)
                                    <div class="cp-small cp-muted">{{ str_replace('-', ' / ', $item->variation) }}</div>
                                @endif
                                @if ($q)
                                    <div class="cp-small" style="margin-top:2px">
                                        <strong data-line-unit>{{ single_price($q['unit_price']) }}</strong>
                                        <span class="cp-strike" data-line-list @if ($q['discount_per_unit'] <= 0) style="display:none" @endif>{{ single_price($q['list_price']) }}</span>
                                        <span class="cp-muted">/ {{ translate('unit') }}</span>
                                    </div>
                                    <div class="cp-small cp-savings" data-line-rule @if (!$q['rule_label']) style="display:none" @endif>{{ $q['rule_label'] }}</div>
                                    <div class="cp-tier-hint" data-line-next @if (!$q['next_tier']) style="display:none" @endif>
                                        @if ($q['next_tier']){{ translate('Add') }} {{ $q['next_tier']['add_qty'] }} {{ translate('more to pay') }} {{ single_price($q['next_tier']['unit_price']) }} {{ translate('each') }}@endif
                                    </div>
                                @endif
                                <div class="cp-error" data-line-issue @if (!$line['issue']) style="display:none" @endif>{{ $line['issue'] }}</div>
                                <div class="cp-line-controls">
                                    <div class="cp-qty">
                                        <button type="button" data-cp-step="-1" aria-label="{{ translate('Decrease') }}">−</button>
                                        <input type="number" name="line_quantity" value="{{ $item->quantity }}" min="{{ $product ? max(1, (int) $product->min_qty) : 1 }}" inputmode="numeric" aria-label="{{ translate('Quantity') }}">
                                        <button type="button" data-cp-step="1" aria-label="{{ translate('Increase') }}">+</button>
                                    </div>
                                    <strong data-line-total>{{ $q ? single_price($q['line_net']) : '—' }}</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('channel_partner.catalog') }}" class="cp-btn cp-btn-light cp-btn-block"><x-cp-icon name="plus" class="cp-icon-sm" /> {{ translate('Add more products') }}</a>
            </div>

            <aside class="cp-card cp-card-pad">
                <h3 style="margin-bottom:10px">{{ translate('Order summary') }}</h3>
                <div class="cp-summary">
                    <div><span class="cp-muted">{{ translate('List price') }}</span><span data-cart-total="list">{{ single_price($t['list']) }}</span></div>
                    <div class="cp-savings" data-cart-savings @if ($t['discount'] <= 0) style="display:none" @endif><span>{{ translate('Partner savings') }}</span><span>− <span data-cart-total="discount">{{ single_price($t['discount']) }}</span></span></div>
                    <div><span class="cp-muted">{{ translate('Subtotal') }}</span><span data-cart-total="net">{{ single_price($t['net']) }}</span></div>
                    <div><span class="cp-muted">{{ translate('Tax') }}</span><span data-cart-total="tax">{{ single_price($t['tax']) }}</span></div>
                    <div><span class="cp-muted">{{ translate('Freight') }}</span><span>{{ translate('As per contract') }}</span></div>
                    <div class="total"><span>{{ translate('Total') }}</span><span data-cart-total="grand">{{ single_price($t['grand']) }}</span></div>
                </div>
                <a href="{{ route('channel_partner.checkout') }}" data-cart-checkout class="cp-btn cp-btn-primary cp-btn-block {{ $blocked ? 'disabled' : '' }}" style="margin-top:14px" aria-disabled="{{ $blocked ? 'true' : 'false' }}">
                    {{ translate('Proceed to checkout') }} <x-cp-icon name="arrow-right" class="cp-icon-sm" />
                </a>
            </aside>
        </div>

        <div class="cp-checkout-bar">
            <div>
                <div class="cp-muted cp-small">{{ translate('Total') }}</div>
                <strong data-cart-total="grand" style="font-size:18px">{{ single_price($t['grand']) }}</strong>
            </div>
            <a href="{{ route('channel_partner.checkout') }}" data-cart-checkout class="cp-btn cp-btn-primary {{ $blocked ? 'disabled' : '' }}" aria-disabled="{{ $blocked ? 'true' : 'false' }}">{{ translate('Checkout') }}</a>
        </div>
    @endif
@endsection
