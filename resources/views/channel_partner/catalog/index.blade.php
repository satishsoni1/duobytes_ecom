@extends('channel_partner.layouts.app')

@section('title', translate('Catalogue'))

@section('content')
    <div class="cp-page-head">
        <div>
            <h1>{{ translate('Catalogue') }}</h1>
            <p>{{ translate('All prices shown are your contract prices (excl. tax).') }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('channel_partner.catalog') }}" role="search">
        <div class="cp-search">
            <x-cp-icon name="search" />
            <input type="search" name="q" value="{{ request('q') }}" class="cp-input" placeholder="{{ translate('Search by product name or SKU') }}" aria-label="{{ translate('Search products') }}" enterkeyhint="search">
            @if (request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
        </div>
        <div class="cp-between" style="margin-top:12px">
            <span class="cp-muted cp-small">{{ $products->total() }} {{ translate('products') }}</span>
            <select name="sort" class="cp-input" style="width:auto;min-height:40px;padding-top:6px;padding-bottom:6px;font-size:14px" onchange="this.form.submit()" aria-label="{{ translate('Sort') }}">
                <option value="">{{ translate('Popular') }}</option>
                <option value="newest" @selected(request('sort') === 'newest')>{{ translate('Newest') }}</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ translate('Price: low to high') }}</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ translate('Price: high to low') }}</option>
                <option value="name" @selected(request('sort') === 'name')>{{ translate('Name A–Z') }}</option>
            </select>
        </div>
    </form>

    <div class="cp-chips" role="list">
        <a href="{{ route('channel_partner.catalog', request()->except(['category', 'page'])) }}" class="cp-chip {{ request('category') ? '' : 'active' }}" role="listitem">{{ translate('All') }}</a>
        @foreach ($categories as $category)
            <a href="{{ route('channel_partner.catalog', array_merge(request()->except('page'), ['category' => $category->id])) }}"
               class="cp-chip {{ (int) request('category') === $category->id ? 'active' : '' }}" role="listitem">{{ $category->getTranslation('name') }}</a>
        @endforeach
    </div>

    @if ($cards->isEmpty())
        <div class="cp-card cp-empty">
            <x-cp-icon name="search" class="cp-icon-lg" />
            <h3 style="color:var(--cp-ink)">{{ translate('No products found') }}</h3>
            <p style="margin-top:6px">{{ translate('Try a different search or category.') }}</p>
            <a href="{{ route('channel_partner.catalog') }}" class="cp-btn cp-btn-light cp-btn-sm">{{ translate('Clear filters') }}</a>
        </div>
    @else
        <div class="cp-products" style="margin-top:8px">
            @foreach ($cards as $card)
                @include('channel_partner.partials.product_card', ['card' => $card, 'cartQty' => $cartQty])
            @endforeach
        </div>
        {{ $products->links('channel_partner.partials.pagination') }}
    @endif
@endsection
