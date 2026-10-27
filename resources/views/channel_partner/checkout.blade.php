@extends('channel_partner.layouts.app')

@section('title', translate('Checkout'))

@php
    $t = $summary['totals'];
    $blocked = $summary['has_issues'] || $summary['min_order_shortfall'] > 0;
    $user = auth()->user();
@endphp

@section('content')
    <a href="{{ route('channel_partner.cart') }}" class="cp-row cp-small" style="margin-bottom:12px;color:var(--cp-ink-2) !important">
        <x-cp-icon name="arrow-left" class="cp-icon-sm" /> {{ translate('Back to cart') }}
    </a>
    <div class="cp-page-head"><h1>{{ translate('Checkout') }}</h1></div>

    @if ($errors->any())
        <div class="cp-alert cp-alert-danger" style="margin-bottom:16px" role="alert">
            <x-cp-icon name="alert" class="cp-icon-sm" /><div>{{ translate('Please check the highlighted fields.') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('channel_partner.checkout.place') }}" class="cp-cart-layout" novalidate>
        @csrf
        <div class="cp-stack">
            <section class="cp-card cp-card-pad">
                <h3 style="margin-bottom:14px">{{ translate('Delivery address') }}</h3>
                <div class="cp-grid cp-md-2">
                    <div class="cp-field">
                        <label class="cp-label" for="shipping_name">{{ translate('Receiver name') }} <span class="req">*</span></label>
                        <input id="shipping_name" name="shipping_name" class="cp-input @error('shipping_name') is-invalid @enderror" value="{{ old('shipping_name', $partner->contact_person) }}" autocomplete="name" required>
                        @error('shipping_name')<div class="cp-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="shipping_phone">{{ translate('Phone') }} <span class="req">*</span></label>
                        <input id="shipping_phone" name="shipping_phone" type="tel" inputmode="tel" class="cp-input @error('shipping_phone') is-invalid @enderror" value="{{ old('shipping_phone', $user->phone) }}" autocomplete="tel" required>
                        @error('shipping_phone')<div class="cp-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="cp-field">
                    <label class="cp-label" for="shipping_address">{{ translate('Address') }} <span class="req">*</span></label>
                    <textarea id="shipping_address" name="shipping_address" rows="2" class="cp-input @error('shipping_address') is-invalid @enderror" autocomplete="street-address" required>{{ old('shipping_address', $partner->address_line) }}</textarea>
                    @error('shipping_address')<div class="cp-error">{{ $message }}</div>@enderror
                </div>
                <div class="cp-grid cp-grid-2">
                    <div class="cp-field">
                        <label class="cp-label" for="shipping_city">{{ translate('City') }} <span class="req">*</span></label>
                        <input id="shipping_city" name="shipping_city" class="cp-input @error('shipping_city') is-invalid @enderror" value="{{ old('shipping_city', $partner->city) }}" autocomplete="address-level2" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="shipping_postal_code">{{ translate('PIN code') }} <span class="req">*</span></label>
                        <input id="shipping_postal_code" name="shipping_postal_code" inputmode="numeric" class="cp-input @error('shipping_postal_code') is-invalid @enderror" value="{{ old('shipping_postal_code', $partner->postal_code) }}" autocomplete="postal-code" required>
                    </div>
                </div>
                <div class="cp-grid cp-grid-2">
                    <div class="cp-field">
                        <label class="cp-label" for="shipping_state">{{ translate('State') }} <span class="req">*</span></label>
                        <input id="shipping_state" name="shipping_state" class="cp-input @error('shipping_state') is-invalid @enderror" value="{{ old('shipping_state', $partner->state) }}" autocomplete="address-level1" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="shipping_country">{{ translate('Country') }}</label>
                        <input id="shipping_country" name="shipping_country" class="cp-input" value="{{ old('shipping_country', $partner->country) }}" autocomplete="country-name">
                    </div>
                </div>
            </section>

            <section class="cp-card cp-card-pad">
                <h3 style="margin-bottom:14px">{{ translate('Purchase order') }}</h3>
                <div class="cp-field">
                    <label class="cp-label" for="po_number">{{ translate('Your PO number') }} <span class="cp-muted cp-small">({{ translate('optional') }})</span></label>
                    <input id="po_number" name="po_number" class="cp-input" value="{{ old('po_number') }}" maxlength="100">
                </div>
                <div class="cp-field" style="margin-bottom:0">
                    <label class="cp-label" for="notes">{{ translate('Notes for our team') }}</label>
                    <textarea id="notes" name="notes" rows="3" class="cp-input" maxlength="1000" placeholder="{{ translate('Delivery window, packing instructions…') }}">{{ old('notes') }}</textarea>
                </div>
            </section>

            <section class="cp-card">
                <div class="cp-card-head"><h3>{{ translate('Items') }} ({{ count($summary['lines']) }})</h3><a href="{{ route('channel_partner.cart') }}" class="cp-small">{{ translate('Edit') }}</a></div>
                <ul class="cp-list">
                    @foreach ($summary['lines'] as $line)
                        <li class="cp-between" style="padding:12px 16px;align-items:flex-start">
                            <div style="min-width:0">
                                <div class="cp-small" style="font-weight:600">{{ $line['product'] ? $line['product']->getTranslation('name') : translate('Unavailable product') }}</div>
                                <div class="cp-small cp-muted">
                                    {{ $line['item']->quantity }} × {{ $line['quote'] ? single_price($line['quote']['unit_price']) : '—' }}
                                    @if ($line['item']->variation) · {{ str_replace('-', ' / ', $line['item']->variation) }} @endif
                                </div>
                                @if ($line['issue'])<div class="cp-error">{{ $line['issue'] }}</div>@endif
                            </div>
                            <strong class="cp-small">{{ $line['quote'] ? single_price($line['quote']['line_net']) : '—' }}</strong>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <aside class="cp-card cp-card-pad">
            <h3 style="margin-bottom:10px">{{ translate('Payment') }}</h3>
            <div class="cp-alert cp-alert-info" style="margin-bottom:14px">
                <x-cp-icon name="credit" class="cp-icon-sm" />
                <div>
                    <strong>{{ translate('Advance payment') }}</strong>
                    <div class="cp-small">{{ translate('Orders are paid in full upfront. Our team will share payment details to confirm the order; it is dispatched once payment is received.') }}</div>
                </div>
            </div>
            <div class="cp-summary">
                <div><span class="cp-muted">{{ translate('List price') }}</span><span>{{ single_price($t['list']) }}</span></div>
                @if ($t['discount'] > 0)
                    <div class="cp-savings"><span>{{ translate('Partner savings') }}</span><span>− {{ single_price($t['discount']) }}</span></div>
                @endif
                <div><span class="cp-muted">{{ translate('Subtotal') }}</span><span>{{ single_price($t['net']) }}</span></div>
                <div><span class="cp-muted">{{ translate('Tax') }}</span><span>{{ single_price($t['tax']) }}</span></div>
                <div class="total"><span>{{ translate('Total') }}</span><span>{{ single_price($t['grand']) }}</span></div>
            </div>
            <button type="submit" class="cp-btn cp-btn-primary cp-btn-block" style="margin-top:14px" @disabled($blocked)>
                <x-cp-icon name="check" class="cp-icon-sm" /> {{ translate('Place order') }}
            </button>
            <p class="cp-muted cp-small" style="margin:10px 0 0;text-align:center">{{ translate('Prices are re-checked when you place the order.') }}</p>
        </aside>
    </form>
@endsection
