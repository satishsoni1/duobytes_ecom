@extends('channel_partner.layouts.base')

@php
    $cpPartner = request()->attributes->get('channel_partner');
    $cpCartCount = $cpPartner ? $cpPartner->cartItems()->count() : 0;
    $cpLogo = get_setting('header_logo');
    $cpNav = [
        ['route' => 'channel_partner.dashboard', 'match' => 'channel_partner.dashboard', 'icon' => 'home', 'label' => translate('Home')],
        ['route' => 'channel_partner.catalog', 'match' => 'channel_partner.catalog*', 'icon' => 'grid', 'label' => translate('Catalogue')],
        ['route' => 'channel_partner.cart', 'match' => 'channel_partner.cart*', 'icon' => 'cart', 'label' => translate('Cart'), 'count' => true],
        ['route' => 'channel_partner.orders.index', 'match' => 'channel_partner.orders.*', 'icon' => 'box', 'label' => translate('Orders')],
        ['route' => 'channel_partner.account', 'match' => 'channel_partner.account*', 'icon' => 'user', 'label' => translate('Account')],
    ];
@endphp

@section('body')
    <header class="cp-topbar">
        <a href="{{ route('channel_partner.dashboard') }}" class="cp-brand">
            @if ($cpLogo)
                <img src="{{ uploaded_asset($cpLogo) }}" alt="{{ get_setting('website_name') }}">
            @else
                <strong>{{ get_setting('website_name') ?: config('app.name') }}</strong>
            @endif
            <span class="cp-brand-tag">{{ translate('Partner') }}</span>
        </a>
        <div class="cp-topbar-actions">
            <a href="{{ route('channel_partner.catalog') }}" class="cp-icon-btn" aria-label="{{ translate('Search catalogue') }}">
                <x-cp-icon name="search" />
            </a>
            <a href="{{ route('channel_partner.cart') }}" class="cp-icon-btn" aria-label="{{ translate('Cart') }}">
                <x-cp-icon name="cart" />
                <span class="cp-badge-dot" data-cart-count data-count="{{ $cpCartCount }}">{{ $cpCartCount }}</span>
            </a>
            <div class="cp-menu" data-cp-menu>
                <button type="button" class="cp-icon-btn" aria-haspopup="true" aria-expanded="false" aria-label="{{ translate('Account menu') }}">
                    <x-cp-icon name="user" />
                </button>
                <div class="cp-menu-panel">
                    <div class="cp-small cp-muted" style="padding:8px 12px 4px">
                        <strong style="color:var(--cp-ink)">{{ $cpPartner?->displayName() ?: auth()->user()->name }}</strong><br>
                        {{ $cpPartner->partner_code ?? '' }}
                    </div>
                    <a href="{{ route('channel_partner.account') }}"><x-cp-icon name="user" class="cp-icon-sm" /> {{ translate('My account') }}</a>
                    <a href="{{ route('home') }}"><x-cp-icon name="home" class="cp-icon-sm" /> {{ translate('Visit store') }}</a>
                    <form method="POST" action="{{ route('channel_partner.logout') }}">
                        @csrf
                        <button type="submit"><x-cp-icon name="logout" class="cp-icon-sm" /> {{ translate('Log out') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="cp-shell">
        <nav class="cp-sidebar" aria-label="{{ translate('Partner navigation') }}">
            @foreach ($cpNav as $item)
                <a href="{{ route($item['route']) }}" class="cp-side-link {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                    <x-cp-icon :name="$item['icon']" /> {{ $item['label'] }}
                    @if (!empty($item['count']) && $cpCartCount)
                        <span class="cp-count" data-cart-count data-count="{{ $cpCartCount }}">{{ $cpCartCount }}</span>
                    @endif
                </a>
            @endforeach
            <div class="cp-side-foot">
                @if (get_setting('helpline_number'))
                    <div class="cp-row"><x-cp-icon name="phone" class="cp-icon-sm" /> <a href="tel:{{ get_setting('helpline_number') }}">{{ get_setting('helpline_number') }}</a></div>
                @endif
                <div style="margin-top:6px">{{ translate('Partner code') }}: <strong>{{ $cpPartner->partner_code ?? '—' }}</strong></div>
            </div>
        </nav>

        <main class="cp-main" id="main">
            @yield('content')
        </main>
    </div>

    <nav class="cp-tabbar" aria-label="{{ translate('Partner navigation') }}">
        @foreach ($cpNav as $item)
            <a href="{{ route($item['route']) }}" class="cp-tab {{ request()->routeIs($item['match']) ? 'active' : '' }}" @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                <x-cp-icon :name="$item['icon']" />
                {{ $item['label'] }}
                @if (!empty($item['count']))
                    <span class="cp-badge-dot" data-cart-count data-count="{{ $cpCartCount }}">{{ $cpCartCount }}</span>
                @endif
            </a>
        @endforeach
    </nav>
@endsection
