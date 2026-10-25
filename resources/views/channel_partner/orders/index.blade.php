@extends('channel_partner.layouts.app')

@section('title', translate('Orders'))

@section('content')
    <div class="cp-page-head">
        <div>
            <h1>{{ translate('Orders') }}</h1>
            <p>{{ translate('Track status, download invoices and re-order.') }}</p>
        </div>
    </div>

    <form method="GET" class="cp-search" role="search" style="margin-bottom:4px">
        <x-cp-icon name="search" />
        <input type="search" name="q" value="{{ request('q') }}" class="cp-input" placeholder="{{ translate('Search order number') }}" aria-label="{{ translate('Search orders') }}">
        @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    </form>
    <div class="cp-chips">
        @foreach (['' => translate('All'), 'pending' => translate('Pending'), 'confirmed' => translate('Confirmed'), 'on_the_way' => translate('On the way'), 'delivered' => translate('Delivered'), 'cancelled' => translate('Cancelled')] as $value => $label)
            <a href="{{ route('channel_partner.orders.index', array_filter(['status' => $value, 'q' => request('q')])) }}" class="cp-chip {{ (string) request('status') === (string) $value ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($orders->isEmpty())
        <div class="cp-card cp-empty">
            <x-cp-icon name="box" class="cp-icon-lg" />
            <h3 style="color:var(--cp-ink)">{{ translate('No orders yet') }}</h3>
            <p style="margin-top:6px">{{ translate('Orders you place will appear here.') }}</p>
            <a href="{{ route('channel_partner.catalog') }}" class="cp-btn cp-btn-primary">{{ translate('Start ordering') }}</a>
        </div>
    @else
        <div class="cp-card">
            <ul class="cp-list">
                @foreach ($orders as $order)
                    <li>
                        <a href="{{ route('channel_partner.orders.show', $order->code) }}" class="cp-list-link">
                            <span class="cp-doc-icon"><x-cp-icon name="box" /></span>
                            <div style="min-width:0;flex:1">
                                <div class="cp-between">
                                    <strong class="cp-small">#{{ $order->code }}</strong>
                                    @include('channel_partner.partials.order_status', ['status' => $order->delivery_status])
                                </div>
                                <div class="cp-between cp-small cp-muted" style="margin-top:2px">
                                    <span>{{ date('d M Y', $order->date) }} · {{ $order->order_details_count }} {{ translate('items') }}@if (!empty($poNumbers[$order->id])) · PO {{ $poNumbers[$order->id] }}@endif</span>
                                    <strong style="color:var(--cp-ink)">{{ single_price($order->grand_total) }}</strong>
                                </div>
                            </div>
                            <x-cp-icon name="chevron-right" class="cp-icon-sm cp-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        {{ $orders->links('channel_partner.partials.pagination') }}
    @endif
@endsection
