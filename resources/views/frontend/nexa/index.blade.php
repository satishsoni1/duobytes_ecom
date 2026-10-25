@extends('frontend.layouts.app')

@section('meta')
    <link rel="stylesheet" href="{{ static_asset('assets/css/home-modern.css') }}?v={{ @filemtime(public_path('assets/css/home-modern.css')) }}">
@endsection

@section('content')
@php
    $lang = get_system_language()->code;
    $decode = fn ($value) => is_array($value) ? $value : (json_decode((string) $value, true) ?: []);

    // Hero slides (desktop + optional mobile art direction)
    $sliderIds = $decode(get_setting('home_slider_images', null, $lang));
    $smallSliderIds = $decode(get_setting('small_home_slider_images', null, $lang));
    $sliderLinks = $decode(get_setting('home_slider_links', null, $lang));
    $slides = collect($sliderIds)->map(fn ($id, $i) => [
        'src' => uploaded_asset($id),
        'small' => isset($smallSliderIds[$i]) ? uploaded_asset($smallSliderIds[$i]) : null,
        'link' => $sliderLinks[$i] ?? null,
    ])->filter(fn ($s) => $s['src'])->values();

    // Banner 1 feeds the two hero side tiles; extra images become a banner row
    $banner1 = get_setting('enable_banner_1') == 1 ? $decode(get_setting('home_banner1_images', null, $lang)) : [];
    $banner1Links = $decode(get_setting('home_banner1_links', null, $lang));
    $sideTiles = collect(array_slice($banner1, 0, 2))->map(fn ($id, $i) => ['src' => uploaded_asset($id), 'link' => $banner1Links[$i] ?? null])->filter(fn ($t) => $t['src'])->values();
    $banner1Rest = collect(array_slice($banner1, 2))->map(fn ($id, $i) => ['src' => uploaded_asset($id), 'link' => $banner1Links[$i + 2] ?? null])->filter(fn ($t) => $t['src'])->values();

    $banner2 = get_setting('enable_banner_2') == 1 ? $decode(get_setting('home_banner2_images', null, $lang)) : [];
    $banner2Links = $decode(get_setting('home_banner2_links', null, $lang));
    $banner3 = get_setting('enable_banner_3') == 1 ? $decode(get_setting('home_banner3_images', null, $lang)) : [];
    $banner3Small = $decode(get_setting('small_home_banner3_images', null, $lang));
    $banner3Links = $decode(get_setting('home_banner3_links', null, $lang));

    $categories = $featured_categories->isNotEmpty()
        ? $featured_categories
        : \App\Models\Category::where('level', 0)->orderBy('order_level', 'desc')->limit(14)->get();

    $flashDeal = get_setting('enable_flash_deal') == 1 ? get_featured_flash_deal() : null;
    $flashProducts = $flashDeal
        ? get_flash_deal_products($flashDeal->id)->pluck('product')->filter(fn ($p) => $p && $p->published == 1)->take(15)
        : collect();

    $todaysDeals = get_setting('enable_todays_deal') == 1
        ? filter_products(\App\Models\Product::where('todays_deal', '1'))->with('thumbnail', 'taxes', 'product_translations')->latest('id')->limit(15)->get()
        : collect();

    $featuredProducts = get_setting('enable_featured_products') == 1 ? get_featured_products()->take(10) : collect();
    $bestSellers = (get_setting('enable_best_selling_products') == 1 && get_setting('best_selling') == 1) ? get_best_selling_products(15) : collect();

    $brands = (get_setting('enable_shop_by_brand') == 1 && get_setting('top_brands')) ? get_brands($decode(get_setting('top_brands'))) : collect();

    $siteName = get_setting('website_name') ?: config('app.name');
@endphp

<div class="tz-home">

    {{-- ============ HERO ============ --}}
    <section class="tz-hero">
        <div class="tz-wrap">
            <div class="tz-bento">
                <div class="tz-hero-main" data-tz-hero>
                    @if ($slides->isNotEmpty())
                        <div class="tz-hero-slides" data-tz-slides>
                            @foreach ($slides as $i => $slide)
                                <a class="tz-hero-slide" href="{{ $slide['link'] ?: 'javascript:void(0)' }}" aria-label="{{ translate('Offer') }} {{ $i + 1 }}">
                                    <picture>
                                        @if ($slide['small'])<source media="(max-width: 767px)" srcset="{{ $slide['small'] }}">@endif
                                        <img src="{{ $slide['src'] }}" alt="{{ $siteName }} {{ translate('offer') }} {{ $i + 1 }}"
                                             @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async"
                                             onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                    </picture>
                                </a>
                            @endforeach
                        </div>
                        @if ($slides->count() > 1)
                            <div class="tz-dots" role="tablist" aria-label="{{ translate('Slides') }}">
                                @foreach ($slides as $i => $slide)
                                    <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-tz-dot="{{ $i }}" aria-label="{{ translate('Go to slide') }} {{ $i + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="tz-hero-fallback">
                            <span class="tz-eyebrow"><i class="las la-bolt"></i> {{ translate('New season') }}</span>
                            <h1>{{ get_setting('site_motto') ?: translate('Toys that spark big imaginations') }}</h1>
                            <p>{{ get_setting('meta_description') ?: translate('Hand-picked toys and learning kits for every age, delivered fast to your door.') }}</p>
                            <div class="tz-hero-ctas">
                                <a href="{{ route('search') }}" class="tz-btn tz-btn-light">{{ translate('Shop now') }} <i class="las la-arrow-right"></i></a>
                                <a href="{{ route('categories.all') }}" class="tz-btn tz-btn-ghost">{{ translate('Browse categories') }}</a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="tz-hero-side">
                    @forelse ($sideTiles as $tile)
                        <a class="tz-tile" href="{{ $tile['link'] ?: 'javascript:void(0)' }}">
                            <img src="{{ $tile['src'] }}" alt="{{ $siteName }} {{ translate('promotion') }}" loading="lazy" decoding="async">
                        </a>
                    @empty
                        <a class="tz-tile tz-tile-accent" href="{{ get_setting('enable_todays_deal') == 1 ? route('todays-deal') : route('search') }}">
                            <span class="tz-tile-title">{{ translate("Today's deals") }}</span>
                            <span class="tz-tile-sub">{{ translate('Fresh offers every day') }} →</span>
                        </a>
                        @if (channel_partner_program_enabled())
                            <a class="tz-tile tz-tile-dark" href="{{ route('channel_partner.landing') }}">
                                <span class="tz-tile-title">{{ translate('Buying in bulk?') }}</span>
                                <span class="tz-tile-sub">{{ translate('Become a channel partner') }} →</span>
                            </a>
                        @else
                            <a class="tz-tile tz-tile-dark" href="{{ route('categories.all') }}">
                                <span class="tz-tile-title">{{ translate('All categories') }}</span>
                                <span class="tz-tile-sub">{{ translate('Find the perfect gift') }} →</span>
                            </a>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- ============ USPs ============ --}}
    <section class="tz-section" style="padding-top:14px;padding-bottom:6px">
        <div class="tz-wrap">
            <div class="tz-usps">
                <div class="tz-usp"><i class="las la-shipping-fast"></i><div><strong>{{ translate('Fast delivery') }}</strong><span>{{ translate('Quick dispatch on every order') }}</span></div></div>
                <div class="tz-usp"><i class="las la-shield-alt"></i><div><strong>{{ translate('Secure payments') }}</strong><span>{{ translate('100% protected checkout') }}</span></div></div>
                <div class="tz-usp"><i class="las la-undo-alt"></i><div><strong>{{ translate('Easy returns') }}</strong><span>{{ translate('Hassle-free return policy') }}</span></div></div>
                <div class="tz-usp"><i class="las la-certificate"></i><div><strong>{{ translate('Genuine products') }}</strong><span>{{ translate('Safety-checked for kids') }}</span></div></div>
            </div>
        </div>
    </section>

    {{-- ============ CATEGORIES ============ --}}
    @if ($categories->isNotEmpty())
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-head">
                    <div><h2>{{ translate('Shop by category') }}</h2></div>
                    <a href="{{ route('categories.all') }}" class="tz-link">{{ translate('View all') }} <i class="las la-angle-right"></i></a>
                </div>
                <div class="tz-rail tz-cats">
                    @foreach ($categories as $category)
                        @php $catImg = uploaded_asset($category->icon ?: ($category->cover_image ?: $category->banner)); @endphp
                        <a href="{{ route('products.category', $category->slug) }}" class="tz-cat">
                            <span class="tz-cat-img">
                                <img src="{{ $catImg ?: static_asset('assets/img/placeholder.jpg') }}" alt="" loading="lazy" decoding="async"
                                     onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                            </span>
                            <span>{{ $category->getTranslation('name') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ FLASH DEAL ============ --}}
    @if ($flashDeal && $flashProducts->isNotEmpty())
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-deal">
                    <div class="tz-deal-head">
                        <div>
                            <h2><i class="las la-bolt"></i> {{ $flashDeal->getTranslation('title') ?: translate('Flash deals') }}</h2>
                            <div class="tz-countdown aiz-count-down mt-2" data-date="{{ date('Y/m/d H:i:s', $flashDeal->end_date) }}"></div>
                        </div>
                        <a href="{{ route('flash-deal-details', $flashDeal->slug) }}" class="tz-link">{{ translate('See all deals') }} <i class="las la-angle-right"></i></a>
                    </div>
                    <div class="tz-rail-wrap">
                        <div class="tz-rail tz-rail-products" data-tz-rail>
                            @foreach ($flashProducts as $product)
                                @include('frontend.nexa.partials.modern_product_card', ['product' => $product])
                            @endforeach
                        </div>
                        <button type="button" class="tz-rail-nav prev" data-tz-rail-prev aria-label="{{ translate('Previous') }}"><i class="las la-angle-left"></i></button>
                        <button type="button" class="tz-rail-nav next" data-tz-rail-next aria-label="{{ translate('Next') }}"><i class="las la-angle-right"></i></button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ TODAY'S DEALS ============ --}}
    @if ($todaysDeals->isNotEmpty())
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-head">
                    <div>
                        <h2>{{ translate("Today's deals") }}</h2>
                        @if (get_setting('todays_deal_title_sub_text'))<p>{{ get_setting('todays_deal_title_sub_text') }}</p>@endif
                    </div>
                    <a href="{{ route('todays-deal') }}" class="tz-link">{{ translate('View all') }} <i class="las la-angle-right"></i></a>
                </div>
                <div class="tz-rail-wrap">
                    <div class="tz-rail tz-rail-products" data-tz-rail>
                        @foreach ($todaysDeals as $product)
                            @include('frontend.nexa.partials.modern_product_card', ['product' => $product])
                        @endforeach
                    </div>
                    <button type="button" class="tz-rail-nav prev" data-tz-rail-prev aria-label="{{ translate('Previous') }}"><i class="las la-angle-left"></i></button>
                    <button type="button" class="tz-rail-nav next" data-tz-rail-next aria-label="{{ translate('Next') }}"><i class="las la-angle-right"></i></button>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ FEATURED ============ --}}
    @if ($featuredProducts->isNotEmpty())
        <section class="tz-section tz-section-tint">
            <div class="tz-wrap">
                <div class="tz-head">
                    <div>
                        <h2>{{ translate('Trending now') }}</h2>
                        <p>{{ get_setting('featured_products_title_sub_text') ?: translate('Our most-loved picks this week') }}</p>
                    </div>
                    <a href="{{ route('featured-products') }}" class="tz-link">{{ translate('View all') }} <i class="las la-angle-right"></i></a>
                </div>
                @if ($featuredProducts->count() >= 10)
                    <div class="tz-grid">
                        @foreach ($featuredProducts as $product)
                            @include('frontend.nexa.partials.modern_product_card', ['product' => $product])
                        @endforeach
                    </div>
                @else
                    {{-- Too few for even grid rows: use a swipeable rail instead --}}
                    <div class="tz-rail-wrap">
                        <div class="tz-rail tz-rail-products" data-tz-rail>
                            @foreach ($featuredProducts as $product)
                                @include('frontend.nexa.partials.modern_product_card', ['product' => $product])
                            @endforeach
                        </div>
                        <button type="button" class="tz-rail-nav prev" data-tz-rail-prev aria-label="{{ translate('Previous') }}"><i class="las la-angle-left"></i></button>
                        <button type="button" class="tz-rail-nav next" data-tz-rail-next aria-label="{{ translate('Next') }}"><i class="las la-angle-right"></i></button>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ============ BANNER 1 (extra images) ============ --}}
    @if ($banner1Rest->isNotEmpty())
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-banners cols-{{ min(3, $banner1Rest->count()) }}">
                    @foreach ($banner1Rest as $banner)
                        <a class="tz-banner" href="{{ $banner['link'] ?: 'javascript:void(0)' }}"><img src="{{ $banner['src'] }}" alt="{{ $siteName }} {{ translate('promotion') }}" loading="lazy" decoding="async"></a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ BEST SELLERS ============ --}}
    @if ($bestSellers->isNotEmpty())
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-head">
                    <div>
                        <h2>{{ translate('Best sellers') }}</h2>
                        <p>{{ get_setting('best_selling_products_title_sub_text') ?: translate('What everyone is buying') }}</p>
                    </div>
                    <a href="{{ route('best-selling') }}" class="tz-link">{{ translate('View all') }} <i class="las la-angle-right"></i></a>
                </div>
                <div class="tz-rail-wrap">
                    <div class="tz-rail tz-rail-products" data-tz-rail>
                        @foreach ($bestSellers as $product)
                            @include('frontend.nexa.partials.modern_product_card', ['product' => $product])
                        @endforeach
                    </div>
                    <button type="button" class="tz-rail-nav prev" data-tz-rail-prev aria-label="{{ translate('Previous') }}"><i class="las la-angle-left"></i></button>
                    <button type="button" class="tz-rail-nav next" data-tz-rail-next aria-label="{{ translate('Next') }}"><i class="las la-angle-right"></i></button>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ CHANNEL PARTNER CTA ============ --}}
    @if (channel_partner_program_enabled())
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-partner">
                    <div>
                        <span class="tz-eyebrow"><i class="las la-handshake"></i> {{ translate('For businesses') }}</span>
                        <h2>{{ translate('Become a') }} {{ $siteName }} {{ translate('channel partner') }}</h2>
                        <p>{{ translate('Distributors, retailers and schools get contract pricing, bulk discounts and a dedicated ordering portal.') }}</p>
                        <div class="tz-hero-ctas" style="margin-top:18px">
                            <a href="{{ route('channel_partner.landing') }}" class="tz-btn tz-btn-light">{{ translate('Apply now') }} <i class="las la-arrow-right"></i></a>
                            <a href="{{ route('channel_partner.login') }}" class="tz-btn tz-btn-ghost">{{ translate('Partner login') }}</a>
                        </div>
                    </div>
                    <ul>
                        <li><i class="las la-percent"></i> {{ translate('Contract pricing on every product') }}</li>
                        <li><i class="las la-layer-group"></i> {{ translate('Bulk quantity price breaks') }}</li>
                        <li><i class="las la-file-invoice"></i> {{ translate('Order on account with GST invoices') }}</li>
                        <li><i class="las la-mobile"></i> {{ translate('Onboard in minutes from your phone') }}</li>
                    </ul>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ BRANDS ============ --}}
    @if ($brands->isNotEmpty())
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-head">
                    <div><h2>{{ translate('Top brands') }}</h2></div>
                    <a href="{{ route('brands.all') }}" class="tz-link">{{ translate('All brands') }} <i class="las la-angle-right"></i></a>
                </div>
                <div class="tz-rail tz-brands">
                    @foreach ($brands as $brand)
                        <a href="{{ route('products.brand', $brand->slug) }}" class="tz-brand" title="{{ $brand->getTranslation('name') }}">
                            <img src="{{ $brand->logo ? uploaded_asset($brand->logo) : static_asset('assets/img/placeholder.jpg') }}" alt="{{ $brand->getTranslation('name') }}" loading="lazy" decoding="async"
                                 onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ BANNER 2 ============ --}}
    @if (count($banner2) > 0)
        <section class="tz-section">
            <div class="tz-wrap">
                <div class="tz-banners cols-{{ min(3, count($banner2)) }}">
                    @foreach ($banner2 as $i => $id)
                        <a class="tz-banner" href="{{ $banner2Links[$i] ?? 'javascript:void(0)' }}"><img src="{{ uploaded_asset($id) }}" alt="{{ $siteName }} {{ translate('promotion') }}" loading="lazy" decoding="async"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';"></a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ NEW ARRIVALS (AJAX, see layout) ============ --}}
    <section class="tz-section">
        <div class="tz-wrap" id="nexa-product-wrapper" data-products-per-row="5">
            <div class="tz-head">
                <div>
                    <h2>{{ translate('New arrivals') }}</h2>
                    <p>{{ translate('Just landed in store') }}</p>
                </div>
                <a href="{{ route('search', ['sort_by' => 'newest']) }}" class="tz-link">{{ translate('View all') }} <i class="las la-angle-right"></i></a>
            </div>
            <div id="section_newest"></div>
            <div class="tz-loadmore d-none" id="view-more-container">
                <button type="button" id="view-more-btn" class="tz-btn tz-btn-primary">{{ translate('Load more') }}</button>
            </div>
        </div>
    </section>

    {{-- ============ BANNER 3 ============ --}}
    @if (count($banner3) > 0)
        <section class="tz-section" style="padding-top:0">
            <div class="tz-wrap">
                <div class="tz-banners">
                    @foreach ($banner3 as $i => $id)
                        <a class="tz-banner" href="{{ $banner3Links[$i] ?? 'javascript:void(0)' }}">
                            <picture>
                                @if (isset($banner3Small[$i]))<source media="(max-width: 767px)" srcset="{{ uploaded_asset($banner3Small[$i]) }}">@endif
                                <img src="{{ uploaded_asset($id) }}" alt="{{ $siteName }} {{ translate('promotion') }}" loading="lazy" decoding="async"
                                     onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </picture>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>

<button id="backToTop" aria-label="{{ translate('Back to top') }}" class="has-transition" data-toggle="tooltip" data-placement="left" title="{{ translate('Back to Top') }}">
    <i class="las la-arrow-up fs-20"></i>
</button>
@endsection

@section('script')
<script>
(function () {
    'use strict';
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Hero: swipeable slides + dots + autoplay (paused on interaction / hidden tab)
    document.querySelectorAll('[data-tz-hero]').forEach(function (hero) {
        var track = hero.querySelector('[data-tz-slides]');
        if (!track) return;
        var dots = hero.querySelectorAll('[data-tz-dot]');
        var count = track.children.length;

        // Use the first banner's natural proportions for the slider frame
        var firstImg = track.querySelector('img');
        function fitRatio() {
            if (firstImg && firstImg.naturalWidth && firstImg.naturalHeight) {
                track.style.setProperty('--tz-hero-ratio', firstImg.naturalWidth + ' / ' + firstImg.naturalHeight);
            }
        }
        if (firstImg) {
            firstImg.addEventListener('load', fitRatio); // also fires when <picture> swaps source on resize
            if (firstImg.complete) fitRatio();
        }
        var index = 0, timer = null;

        function go(i) { index = (i + count) % count; track.scrollTo({ left: track.clientWidth * index, behavior: reduceMotion ? 'auto' : 'smooth' }); }
        function sync() {
            index = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
            dots.forEach(function (d, i) { d.classList.toggle('active', i === index); });
        }
        function start() { if (count > 1 && !reduceMotion) { stop(); timer = setInterval(function () { if (!document.hidden) go(index + 1); }, 5000); } }
        function stop() { clearInterval(timer); }

        track.addEventListener('scroll', function () { window.requestAnimationFrame(sync); }, { passive: true });
        dots.forEach(function (d) { d.addEventListener('click', function () { go(parseInt(d.getAttribute('data-tz-dot'), 10)); start(); }); });
        ['mouseenter', 'touchstart', 'focusin'].forEach(function (ev) { hero.addEventListener(ev, stop, { passive: true }); });
        ['mouseleave', 'touchend', 'focusout'].forEach(function (ev) { hero.addEventListener(ev, start, { passive: true }); });
        start();
    });

    // Product rails: desktop arrow buttons
    document.querySelectorAll('.tz-rail-wrap').forEach(function (wrap) {
        var rail = wrap.querySelector('[data-tz-rail]');
        if (!rail) return;
        function step(dir) { rail.scrollBy({ left: dir * rail.clientWidth * 0.9, behavior: reduceMotion ? 'auto' : 'smooth' }); }
        var prev = wrap.querySelector('[data-tz-rail-prev]'), next = wrap.querySelector('[data-tz-rail-next]');
        prev && prev.addEventListener('click', function () { step(-1); });
        next && next.addEventListener('click', function () { step(1); });
    });

    // New arrivals: show "Load more" once the first page has rendered
    var newest = document.getElementById('section_newest');
    if (newest && window.MutationObserver) {
        new MutationObserver(function () {
            if (newest.children.length) document.getElementById('view-more-container').classList.remove('d-none');
        }).observe(newest, { childList: true });
    }

    var page = 1;
    $(document).on('click', '#view-more-btn', function () {
        var $button = $(this);
        var original = $button.html();
        page++;
        $button.prop('disabled', true).html('{{ translate('Loading...') }}');
        $.post('{{ route('home.section.newest_products') }}', {
            _token: '{{ csrf_token() }}',
            page: page,
            limit: 15
        }, function (data) {
            if ($.trim(data) === '') {
                $button.text('{{ translate('No more products') }}');
            } else {
                $('#newest-products-list').append(data);
                $button.prop('disabled', false).html(original);
            }
        }).fail(function () {
            page--;
            $button.prop('disabled', false).html('{{ translate('Error, try again') }}');
        });
    });

    // Back to top
    var btn = document.getElementById('backToTop');
    if (btn) {
        var raf = null;
        var toggle = function () { btn.classList.toggle('show', window.scrollY > 100); raf = null; };
        window.addEventListener('scroll', function () { if (!raf) raf = requestAnimationFrame(toggle); }, { passive: true });
        btn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); });
        toggle();
    }
})();
</script>
@endsection
