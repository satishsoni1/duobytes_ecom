@extends('channel_partner.layouts.app')

@php
    $name = $product->getTranslation('name');
    $first = $variants->first(fn ($v) => $v['stock'] > 0) ?? $variants->first();
    $quote = $first['quote'];
    $minQty = max(1, (int) $product->min_qty);
    $existing = $inCart->get($first['variant']);
@endphp

@section('title', $name)

@section('content')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('channel_partner.catalog') }}" class="cp-row cp-small" style="margin-bottom:12px;color:var(--cp-ink-2) !important">
        <x-cp-icon name="arrow-left" class="cp-icon-sm" /> {{ translate('Back to catalogue') }}
    </a>

    <div class="cp-pdp">
        <div class="cp-gallery" aria-label="{{ translate('Product images') }}">
            @foreach ($photos as $photo)
                <img src="{{ $photo }}" alt="{{ $name }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                     onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}'">
            @endforeach
        </div>

        <div data-cp-quote data-product-id="{{ $product->id }}">
            @if ($product->brand)
                <div class="cp-muted cp-small" style="font-weight:600">{{ $product->brand->getTranslation('name') }}</div>
            @endif
            <h1 style="margin-top:4px">{{ $name }}</h1>
            <div class="cp-muted cp-small" style="margin-top:4px">SKU: {{ $first['sku'] ?: '—' }} · {{ translate('Unit') }}: {{ $product->unit ?: translate('pc') }}</div>

            <div class="cp-card cp-card-pad" style="margin-top:16px">
                <div class="cp-muted cp-small" style="font-weight:600">{{ translate('Your price') }} ({{ translate('per unit, excl. tax') }})</div>
                <div class="cp-price-row" style="margin-top:4px">
                    <span class="cp-price" style="font-size:28px" data-cp-unit>{{ single_price($quote['unit_price']) }}</span>
                    <span class="cp-strike" data-cp-list @if ($quote['discount_per_unit'] <= 0) style="display:none" @endif>{{ single_price($quote['list_price']) }}</span>
                </div>
                @if ($quote['rule_label'])
                    <div class="cp-pill cp-pill-success" style="margin-top:8px"><x-cp-icon name="percent" class="cp-icon-sm" /> {{ $quote['rule_label'] }}</div>
                @endif
                <div class="cp-tier-hint" style="margin-top:8px;font-size:14px;{{ $quote['next_tier'] ? '' : 'display:none' }}" data-cp-next>
                    @if ($quote['next_tier'])
                        {{ translate('Add') }} {{ $quote['next_tier']['add_qty'] }} {{ translate('more to pay') }} {{ single_price($quote['next_tier']['unit_price']) }} {{ translate('each') }}
                    @endif
                </div>
            </div>

            @if (count($tiers) > 0)
                <div class="cp-card" style="margin-top:16px;overflow:hidden">
                    <div class="cp-card-head"><h3>{{ translate('Bulk pricing') }}</h3></div>
                    <table class="cp-tiers">
                        <thead><tr><th>{{ translate('Quantity') }}</th><th>{{ translate('Unit price') }}</th><th>{{ translate('Deal') }}</th></tr></thead>
                        <tbody>
                            @foreach ($tiers as $tier)
                                <tr data-cp-tier="{{ $tier['min_qty'] }}" class="{{ $minQty >= $tier['min_qty'] && (!isset($tiers[$loop->index + 1]) || $minQty < $tiers[$loop->index + 1]['min_qty']) ? 'active' : '' }}">
                                    <td>{{ $tier['min_qty'] }}+</td>
                                    <td>{{ single_price($tier['unit_price']) }}</td>
                                    <td class="cp-small">{{ $tier['label'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <form method="POST" action="{{ route('channel_partner.cart.store') }}" data-cp-add style="margin-top:16px">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if ($variants->count() > 1)
                    <div class="cp-field">
                        <span class="cp-label">{{ translate('Option') }}</span>
                        <div class="cp-variants">
                            @foreach ($variants as $variant)
                                <div class="cp-variant">
                                    <input type="radio" name="variation" id="var_{{ $loop->index }}" value="{{ $variant['variant'] }}" data-stock="{{ $variant['stock'] }}"
                                           @checked($variant['variant'] === $first['variant']) @disabled($variant['stock'] <= 0)>
                                    <label for="var_{{ $loop->index }}">
                                        {{ $variant['label'] }}
                                        <small>{{ single_price($variant['quote']['unit_price']) }} · {{ $variant['stock'] > 0 ? $variant['stock'] . ' ' . translate('in stock') : translate('Out of stock') }}</small>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <input type="hidden" name="variation" value="{{ $first['variant'] }}">
                @endif

                <input type="hidden" name="mode" value="add">

                <div class="cp-buybar">
                    <div class="cp-between" style="margin-bottom:10px">
                        <div class="cp-qty">
                            <button type="button" data-cp-step="-1" aria-label="{{ translate('Decrease') }}">−</button>
                            <input type="number" name="quantity" value="{{ $minQty }}" min="{{ $minQty }}" max="{{ max($minQty, $first['stock']) }}" inputmode="numeric" aria-label="{{ translate('Quantity') }}">
                            <button type="button" data-cp-step="1" aria-label="{{ translate('Increase') }}">+</button>
                        </div>
                        <div style="text-align:right">
                            <div class="cp-muted cp-small">{{ translate('Line total') }}</div>
                            <strong data-cp-line style="font-size:18px">{{ single_price($quote['line_net']) }}</strong>
                        </div>
                    </div>
                    @if ($first['stock'] > 0)
                        <button type="submit" class="cp-btn cp-btn-primary cp-btn-block"><x-cp-icon name="cart" class="cp-icon-sm" /> {{ translate('Add to cart') }}</button>
                    @else
                        <button type="button" class="cp-btn cp-btn-light cp-btn-block" disabled>{{ translate('Out of stock') }}</button>
                    @endif
                    <div class="cp-small cp-muted" style="text-align:center;margin-top:8px">
                        <span data-cp-stock>{{ $first['stock'] }}</span> {{ translate('available') }}
                        @if ($minQty > 1) · {{ translate('Min. order') }} {{ $minQty }} @endif
                        @if ($existing) · {{ $existing->quantity }} {{ translate('already in cart') }} @endif
                    </div>
                </div>
            </form>

            @if ($product->getTranslation('description'))
                <div class="cp-card cp-card-pad" style="margin-top:16px">
                    <h3 style="margin-bottom:10px">{{ translate('Description') }}</h3>
                    <div class="cp-small" style="color:var(--cp-ink-2);overflow-wrap:anywhere">{!! $product->getTranslation('description') !!}</div>
                </div>
            @endif
        </div>
    </div>
@endsection
