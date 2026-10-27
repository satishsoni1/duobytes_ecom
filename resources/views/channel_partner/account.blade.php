@extends('channel_partner.layouts.app')

@section('title', translate('Account'))

@section('content')
    <div class="cp-page-head"><h1>{{ translate('Account') }}</h1></div>

    <div class="cp-grid cp-md-2" style="align-items:start">
        <section class="cp-card">
            <div class="cp-card-head"><h3>{{ translate('Business profile') }}</h3><span class="cp-pill cp-pill-success">{{ translate('Approved') }}</span></div>
            <dl class="cp-review-list" style="padding:0 16px">
                <div><dt>{{ translate('Partner code') }}</dt><dd>{{ $partner->partner_code }}</dd></div>
                <div><dt>{{ translate('Business') }}</dt><dd>{{ $partner->company_name ?: '—' }}</dd></div>
                <div><dt>{{ translate('Contact') }}</dt><dd>{{ $partner->contact_person }}</dd></div>
                <div><dt>{{ translate('Email') }}</dt><dd>{{ auth()->user()->email }}</dd></div>
                <div><dt>{{ translate('Mobile') }}</dt><dd>{{ auth()->user()->phone }}</dd></div>
                <div><dt>{{ translate('GSTIN') }}</dt><dd>{{ $partner->gst_number ?: '—' }}</dd></div>
                <div style="border-bottom:0"><dt>{{ translate('Address') }}</dt><dd>{{ $partner->address_line }}, {{ $partner->city }}, {{ $partner->state }} {{ $partner->postal_code }}</dd></div>
            </dl>
            <p class="cp-small cp-muted" style="padding:0 16px 16px;margin:0">{{ translate('To change business details, please contact your account manager.') }}</p>
        </section>

        <div class="cp-stack">
            <section class="cp-card">
                <div class="cp-card-head"><h3>{{ translate('Contract') }}</h3></div>
                <dl class="cp-review-list" style="padding:0 16px 6px">
                    <div><dt>{{ translate('Reference') }}</dt><dd>{{ $partner->contract_reference ?: '—' }}</dd></div>
                    <div><dt>{{ translate('Valid until') }}</dt><dd>{{ $partner->contract_valid_until ? $partner->contract_valid_until->format('d M Y') : '—' }}</dd></div>
                    <div><dt>{{ translate('Payment') }}</dt><dd>{{ translate('Advance payment') }}</dd></div>
                    <div style="border-bottom:0"><dt>{{ translate('Minimum order') }}</dt><dd>{{ $partner->min_order_value > 0 ? single_price($partner->min_order_value) : '—' }}</dd></div>
                </dl>
            </section>

            <section class="cp-card">
                <div class="cp-card-head"><h3>{{ translate('Documents') }}</h3></div>
                <ul class="cp-list">
                    @foreach ($partner->documents as $doc)
                        <li>
                            <a href="{{ route('channel_partner.documents.show', $doc) }}" target="_blank" rel="noopener" class="cp-list-link">
                                <x-cp-icon name="file" class="cp-icon-sm cp-muted" />
                                <span class="cp-small" style="min-width:0;flex:1">{{ $doc->typeLabel() }}<br><span class="cp-muted">{{ $doc->original_name }}</span></span>
                                <span class="cp-pill {{ $doc->status === 'verified' ? 'cp-pill-success' : ($doc->status === 'rejected' ? 'cp-pill-danger' : 'cp-pill-muted') }}">{{ translate(ucfirst($doc->status)) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="cp-card cp-card-pad">
                <h3 style="margin-bottom:14px">{{ translate('Change password') }}</h3>
                <form method="POST" action="{{ route('channel_partner.account.password') }}" novalidate>
                    @csrf
                    <div class="cp-field">
                        <label class="cp-label" for="current_password">{{ translate('Current password') }}</label>
                        <input id="current_password" name="current_password" type="password" class="cp-input @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                        @error('current_password')<div class="cp-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="password">{{ translate('New password') }}</label>
                        <input id="password" name="password" type="password" class="cp-input @error('password') is-invalid @enderror" autocomplete="new-password" minlength="6" required>
                        <div class="cp-hint">{{ translate('At least 6 characters.') }}</div>
                        @error('password')<div class="cp-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="password_confirmation">{{ translate('Confirm new password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="cp-input" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="cp-btn cp-btn-primary cp-btn-block">{{ translate('Update password') }}</button>
                </form>
            </section>

            <form method="POST" action="{{ route('channel_partner.logout') }}">
                @csrf
                <button type="submit" class="cp-btn cp-btn-danger cp-btn-block"><x-cp-icon name="logout" class="cp-icon-sm" /> {{ translate('Log out') }}</button>
            </form>
        </div>
    </div>
@endsection
