@extends('channel_partner.layouts.app')

@section('title', translate('Dashboard'))

@section('content')
    @if ($partner->contractExpired())
        <div class="cp-alert cp-alert-danger" style="margin-bottom:16px" role="alert">
            <x-cp-icon name="alert" class="cp-icon-sm" />
            <div>{{ translate('Your partner contract expired on') }} {{ $partner->contract_valid_until->format('d M Y') }}. {{ translate('Ordering is paused until it is renewed.') }}</div>
        </div>
    @elseif ($partner->contract_valid_until && now()->diffInDays($partner->contract_valid_until, false) <= 30)
        <div class="cp-alert cp-alert-warning" style="margin-bottom:16px">
            <x-cp-icon name="clock" class="cp-icon-sm" />
            <div>{{ translate('Your contract renews on') }} {{ $partner->contract_valid_until->format('d M Y') }}.</div>
        </div>
    @endif

    <section class="cp-hero">
        <div class="cp-small" style="opacity:.85">{{ translate('Welcome back') }}</div>
        <h1 style="margin-top:2px">{{ $partner->displayName() }}</h1>
        <p>{{ translate('Partner code') }} {{ $partner->partner_code }}@if ($partner->default_discount > 0) · {{ rtrim(rtrim(number_format($partner->default_discount, 2), '0'), '.') }}% {{ translate('partner discount') }}@endif</p>
        <div class="cp-row" style="margin-top:16px;flex-wrap:wrap">
            <a href="{{ route('channel_partner.catalog') }}" class="cp-btn" style="background:#fff;color:var(--cp-primary) !important"><x-cp-icon name="grid" class="cp-icon-sm" /> {{ translate('Browse catalogue') }}</a>
            <a href="{{ route('channel_partner.cart') }}" class="cp-btn" style="color:#fff !important;border-color:rgba(255,255,255,.4)"><x-cp-icon name="cart" class="cp-icon-sm" /> {{ translate('View cart') }}</a>
        </div>
    </section>

    <section class="cp-grid cp-grid-2 cp-grid-4" style="margin-top:16px">
        <div class="cp-card cp-stat"><div class="cp-stat-label">{{ translate('Total orders') }}</div><div class="cp-stat-value">{{ $stats['orders'] }}</div></div>
        <div class="cp-card cp-stat"><div class="cp-stat-label">{{ translate('In progress') }}</div><div class="cp-stat-value">{{ $stats['open'] }}</div></div>
        <div class="cp-card cp-stat"><div class="cp-stat-label">{{ translate('Purchases') }}</div><div class="cp-stat-value" style="font-size:18px">{{ single_price($stats['spend']) }}</div></div>
        <div class="cp-card cp-stat"><div class="cp-stat-label">{{ translate('You saved') }}</div><div class="cp-stat-value cp-savings" style="font-size:18px">{{ single_price($stats['saved']) }}</div></div>
    </section>

    <div class="cp-grid cp-md-2" style="margin-top:16px;align-items:start">
        <section class="cp-card">
            <div class="cp-card-head"><h3>{{ translate('Recent orders') }}</h3><a href="{{ route('channel_partner.orders.index') }}" class="cp-small">{{ translate('View all') }}</a></div>
            @if ($recentOrders->isEmpty())
                <div class="cp-empty" style="padding:28px 16px">{{ translate('No orders yet.') }}</div>
            @else
                <ul class="cp-list">
                    @foreach ($recentOrders as $order)
                        <li>
                            <a href="{{ route('channel_partner.orders.show', $order->code) }}" class="cp-list-link">
                                <div style="min-width:0;flex:1">
                                    <div class="cp-between"><strong class="cp-small">#{{ $order->code }}</strong>@include('channel_partner.partials.order_status', ['status' => $order->delivery_status])</div>
                                    <div class="cp-between cp-small cp-muted" style="margin-top:2px"><span>{{ date('d M Y', $order->date) }}</span><strong style="color:var(--cp-ink)">{{ single_price($order->grand_total) }}</strong></div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="cp-card">
            <div class="cp-card-head"><h3>{{ translate('Your contract pricing') }}</h3></div>
            @if ($rules->isEmpty() && $partner->default_discount <= 0)
                <div class="cp-empty" style="padding:28px 16px">{{ translate('Your contract prices will appear here once set up by your account manager.') }}</div>
            @else
                <ul class="cp-list">
                    @if ($partner->default_discount > 0)
                        <li class="cp-between" style="padding:12px 16px"><span class="cp-small">{{ translate('All products') }}</span><span class="cp-pill cp-pill-success">{{ rtrim(rtrim(number_format($partner->default_discount, 2), '0'), '.') }}% {{ translate('off') }}</span></li>
                    @endif
                    @foreach ($rules->take(8) as $rule)
                        <li class="cp-between" style="padding:12px 16px">
                            <span class="cp-small" style="min-width:0">{{ $rule->targetLabel() }}@if ($rule->ends_at)<span class="cp-muted"> · {{ translate('till') }} {{ $rule->ends_at->format('d M') }}</span>@endif</span>
                            <span class="cp-pill cp-pill-success">{{ $rule->describe() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    @if ($featured->isNotEmpty())
        <section style="margin-top:20px">
            <div class="cp-between" style="margin-bottom:12px"><h2>{{ translate('Popular with partners') }}</h2><a href="{{ route('channel_partner.catalog') }}" class="cp-small">{{ translate('See all') }}</a></div>
            <div class="cp-products">
                @foreach ($featured as $item)
                    @php $p = $item['product']; $q = $item['quote']; @endphp
                    <a href="{{ route('channel_partner.catalog.show', $p->slug) }}" class="cp-card cp-product" style="color:inherit">
                        <span class="cp-product-media"><img src="{{ uploaded_asset($p->thumbnail_img) ?: static_asset('assets/img/placeholder.jpg') }}" alt="{{ $p->getTranslation('name') }}" loading="lazy" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}'"></span>
                        <span class="cp-product-body">
                            <span class="cp-product-name">{{ $p->getTranslation('name') }}</span>
                            <span class="cp-price-row"><span class="cp-price">{{ single_price($q['unit_price']) }}</span>@if ($q['discount_per_unit'] > 0)<span class="cp-strike cp-small">{{ single_price($q['list_price']) }}</span>@endif</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
