@php
    /** @var \App\Models\Product $product */
    $product = $card['product'];
    $quote = $card['quote'];
    $minQty = max(1, (int) $product->min_qty);
    $name = $product->getTranslation('name');
    $url = route('channel_partner.catalog.show', $product->slug);
    $inCart = (int) ($cartQty[$product->id] ?? 0);
@endphp
<article class="cp-card cp-product" data-cp-quote data-product-id="{{ $product->id }}">
    <a href="{{ $url }}" class="cp-product-media">
        <img src="{{ uploaded_asset($product->thumbnail_img) ?: static_asset('assets/img/placeholder.jpg') }}" alt="{{ $name }}" loading="lazy"
             onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}'">
        @if ($quote['discount_per_unit'] > 0 && $quote['list_price'] > 0)
            <span class="cp-product-flag cp-pill cp-pill-success">-{{ round($quote['discount_per_unit'] / $quote['list_price'] * 100) }}%</span>
        @endif
    </a>
    <div class="cp-product-body">
        <a href="{{ $url }}" class="cp-product-name">{{ $name }}</a>
        <div class="cp-price-row">
            <span class="cp-price" data-cp-unit>{{ single_price($quote['unit_price']) }}</span>
            <span class="cp-strike cp-small" data-cp-list @if ($quote['discount_per_unit'] <= 0) style="display:none" @endif>{{ single_price($quote['list_price']) }}</span>
        </div>
        @if (!empty($card['tiers']) && count($card['tiers']) > 1)
            <div class="cp-tier-hint"><x-cp-icon name="tag" class="cp-icon-sm" style="vertical-align:-3px" /> {{ count($card['tiers']) }} {{ translate('bulk price tiers') }}</div>
        @endif
        <div class="cp-tier-hint" data-cp-next style="display:none"></div>

        <div class="cp-product-foot">
            @if ($quote['stock'] <= 0)
                <span class="cp-pill cp-pill-muted">{{ translate('Out of stock') }}</span>
            @elseif ($card['has_variants'])
                <a href="{{ $url }}" class="cp-btn cp-btn-light cp-btn-sm cp-btn-block">{{ translate('Choose options') }}</a>
            @else
                <form method="POST" action="{{ route('channel_partner.cart.store') }}" data-cp-add class="cp-grid" style="gap:8px">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="variation" value="{{ $product->stocks->first()->variant ?? '' }}">
                    <div class="cp-qty cp-qty-block">
                        <button type="button" data-cp-step="-1" aria-label="{{ translate('Decrease') }}">−</button>
                        <input type="number" name="quantity" value="{{ $minQty }}" min="{{ $minQty }}" max="{{ $quote['stock'] }}" inputmode="numeric" aria-label="{{ translate('Quantity') }}">
                        <button type="button" data-cp-step="1" aria-label="{{ translate('Increase') }}">+</button>
                    </div>
                    <button type="submit" class="cp-btn cp-btn-primary cp-btn-sm cp-btn-block">
                        <x-cp-icon name="cart" class="cp-icon-sm" /> {{ translate('Add') }}
                    </button>
                </form>
                @if ($inCart)
                    <div class="cp-small cp-muted" style="margin-top:6px">{{ $inCart }} {{ translate('in cart') }}</div>
                @endif
            @endif
        </div>
    </div>
</article>
