@php
    $tzName = $product->getTranslation('name');
    $tzUrl = $product->auction_product == 1 ? route('auction-product', $product->slug) : route('product', $product->slug);
    $tzImg = get_image($product->thumbnail);
    $tzAltId = collect(explode(',', (string) $product->photos))->filter()->first(fn ($id) => $id != $product->thumbnail_img);
    $tzAlt = $tzAltId ? uploaded_asset($tzAltId) : null;
    $tzBase = home_base_price($product);
    $tzPrice = home_discounted_base_price($product);
    $tzOff = discount_in_percentage($product);
    $tzColors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
    $tzAttrs = is_string($product->attributes) ? json_decode($product->attributes, true) : $product->attributes;
    $tzHasVariants = (is_array($tzColors) && count($tzColors) > 0) || (is_array($tzAttrs) && count($tzAttrs) > 0);
    $tzCanBuy = Auth::check() || get_setting('guest_checkout_activation') == 1;
@endphp
<article class="tz-card">
    <a href="{{ $tzUrl }}" class="tz-card-media" title="{{ $tzName }}">
        <img class="main" src="{{ $tzImg }}" alt="{{ $tzName }}" loading="lazy" decoding="async"
             onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
        @if ($tzAlt && $tzAlt !== $tzImg)
            <img class="alt" src="{{ $tzAlt }}" alt="" loading="lazy" decoding="async" aria-hidden="true"
                 onerror="this.remove()">
        @endif
    </a>
    <div class="tz-badges">
        @if ($tzOff > 0)<span class="tz-badge sale">-{{ $tzOff }}%</span>@endif
        @if ($product->wholesale_product)<span class="tz-badge">{{ translate('Wholesale') }}</span>@endif
    </div>
    @if ($product->auction_product == 0)
        <button type="button" class="tz-wish" onclick="addToWishList({{ $product->id }})" aria-label="{{ translate('Add to wishlist') }}">
            <i class="la la-heart-o"></i>
        </button>
    @endif
    <div class="tz-card-body">
        <a href="{{ $tzUrl }}" class="tz-card-name">{{ $tzName }}</a>
        @if ($product->rating > 0)
            <div class="tz-rating"><i class="las la-star"></i> {{ number_format($product->rating, 1) }}</div>
        @endif
        <div class="tz-price">
            <strong>{{ $tzPrice }}</strong>
            @if ($tzBase != $tzPrice)<del>{{ $tzBase }}</del>@endif
        </div>
        @if ($product->auction_product == 0)
            <div class="tz-card-foot">
                @if ($tzHasVariants)
                    <button type="button" class="tz-add" onclick="showAddToCartRightCanvas({{ $product->id }})">
                        <i class="las la-sliders-h"></i> {{ translate('Choose options') }}
                    </button>
                @else
                    <button type="button" class="tz-add" @if ($tzCanBuy) onclick="addToCartSingleProduct({{ $product->id }})" @else onclick="showLoginModal()" @endif>
                        <i class="las la-shopping-bag"></i> {{ translate('Add to cart') }}
                    </button>
                @endif
            </div>
        @endif
    </div>
</article>
