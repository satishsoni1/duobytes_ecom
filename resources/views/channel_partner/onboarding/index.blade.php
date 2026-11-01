@extends('channel_partner.layouts.auth')

@php
    use App\Models\ChannelPartner;

    $editable = $partner->canEditApplication();
    $requested = request('step');
    if (!$editable) {
        $step = 'status';
    } elseif (in_array($requested, ['business', 'review'], true)) {
        $step = $requested;
    } else {
        $step = $partner->businessDetailsComplete() ? 'review' : 'business';
    }
    // Do not let partners jump ahead of an incomplete business step
    if ($step === 'review' && !$partner->businessDetailsComplete()) {
        $step = 'business';
    }
    $stepNumber = ['business' => 2, 'review' => 3, 'status' => 3][$step];
@endphp

@section('title', translate('Partner application'))
@section('auth_width', 'wide')

@section('content')
    @if ($step !== 'status')
        @include('channel_partner.onboarding.partials.steps', ['current' => $stepNumber])
    @endif

    @if ($partner->status === ChannelPartner::STATUS_REJECTED && $step !== 'status')
        <div class="cp-alert cp-alert-danger" style="margin-bottom:16px" role="alert">
            <x-cp-icon name="alert" class="cp-icon-sm" />
            <div>
                <strong>{{ translate('Changes requested') }}</strong><br>
                {{ $partner->rejection_reason }}
                <div class="cp-small" style="margin-top:4px">{{ translate('Update the details below and submit again.') }}</div>
            </div>
        </div>
    @endif

    {{-- ===================== STEP 2: BUSINESS ===================== --}}
    @if ($step === 'business')
        <h1 class="cp-auth-title">{{ translate('Your details') }}</h1>
        <p class="cp-auth-sub">{{ translate('Just your name, contact and address. This appears on your invoices.') }}</p>

        <form method="POST" action="{{ route('channel_partner.onboarding.business') }}" data-cp-ajax
              data-cp-next="{{ route('channel_partner.onboarding', ['step' => 'review']) }}" novalidate>
            @csrf
            <div class="cp-card cp-card-pad">
                <div class="cp-field">
                    <label class="cp-label" for="company_name">{{ translate('Business / company name') }} <span class="cp-muted cp-small">({{ translate('optional') }})</span></label>
                    <input id="company_name" name="company_name" class="cp-input" value="{{ old('company_name', $partner->company_name) }}" autocomplete="organization" maxlength="150">
                    <div class="cp-muted cp-small">{{ translate('Leave blank if you are applying as an individual.') }}</div>
                </div>
                <div class="cp-field">
                    <span class="cp-label">{{ translate('Business type') }} <span class="req">*</span></span>
                    <div class="cp-variants" role="radiogroup">
                        @foreach ($businessTypes as $value => $label)
                            <div class="cp-variant">
                                <input type="radio" id="bt_{{ $value }}" name="business_type" value="{{ $value }}" {{ old('business_type', $partner->business_type) === $value ? 'checked' : '' }}>
                                <label for="bt_{{ $value }}">{{ translate($label) }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="cp-grid cp-md-2">
                    <div class="cp-field">
                        <label class="cp-label" for="contact_person">{{ translate('Contact person') }} <span class="req">*</span></label>
                        <input id="contact_person" name="contact_person" class="cp-input" value="{{ old('contact_person', $partner->contact_person) }}" autocomplete="name" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="designation">{{ translate('Designation') }}</label>
                        <input id="designation" name="designation" class="cp-input" value="{{ old('designation', $partner->designation) }}" placeholder="{{ translate('Owner, Purchase Manager…') }}">
                    </div>
                </div>
            </div>

            <div class="cp-card cp-card-pad" style="margin-top:16px">
                <h3 style="margin-bottom:14px">{{ translate('Business address') }}</h3>
                <div class="cp-field">
                    <label class="cp-label" for="address_line">{{ translate('Address') }} <span class="req">*</span></label>
                    <textarea id="address_line" name="address_line" class="cp-input" rows="2" autocomplete="street-address" required>{{ old('address_line', $partner->address_line) }}</textarea>
                </div>
                <div class="cp-grid cp-grid-2">
                    <div class="cp-field">
                        <label class="cp-label" for="city">{{ translate('City') }} <span class="req">*</span></label>
                        <input id="city" name="city" class="cp-input" value="{{ old('city', $partner->city) }}" autocomplete="address-level2" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="postal_code">{{ translate('PIN code') }} <span class="req">*</span></label>
                        <input id="postal_code" name="postal_code" class="cp-input" value="{{ old('postal_code', $partner->postal_code) }}" inputmode="numeric" autocomplete="postal-code" required>
                    </div>
                </div>
                <div class="cp-grid cp-grid-2">
                    <div class="cp-field">
                        <label class="cp-label" for="state">{{ translate('State') }} <span class="req">*</span></label>
                        <input id="state" name="state" class="cp-input" value="{{ old('state', $partner->state) }}" autocomplete="address-level1" required>
                    </div>
                    <div class="cp-field">
                        <label class="cp-label" for="country">{{ translate('Country') }}</label>
                        <input id="country" name="country" class="cp-input" value="{{ old('country', $partner->country ?: 'India') }}" autocomplete="country-name">
                    </div>
                </div>
            </div>

            <div class="cp-sticky-actions">
                <button type="submit" class="cp-btn cp-btn-primary">{{ translate('Save & continue') }} <x-cp-icon name="arrow-right" class="cp-icon-sm" /></button>
            </div>
        </form>
    @endif

    {{-- ===================== STEP 3: REVIEW ===================== --}}
    @if ($step === 'review')
        <h1 class="cp-auth-title">{{ translate('Review & submit') }}</h1>
        <p class="cp-auth-sub">{{ translate('Check everything once. Your partner account is activated as soon as you submit.') }}</p>

        <div class="cp-card">
            <div class="cp-card-head">
                <h3>{{ translate('Business') }}</h3>
                <a href="{{ route('channel_partner.onboarding', ['step' => 'business']) }}" class="cp-small">{{ translate('Edit') }}</a>
            </div>
            <dl class="cp-review-list" style="padding:0 16px">
                <div><dt>{{ translate('Business') }}</dt><dd>{{ $partner->company_name ?: '—' }}</dd></div>
                <div><dt>{{ translate('Type') }}</dt><dd>{{ translate($businessTypes[$partner->business_type] ?? '-') }}</dd></div>
                <div><dt>{{ translate('Contact') }}</dt><dd>{{ $partner->contact_person }}{{ $partner->designation ? ', ' . $partner->designation : '' }}</dd></div>
                <div><dt>{{ translate('Email') }}</dt><dd>{{ auth()->user()->email }}</dd></div>
                <div><dt>{{ translate('Mobile') }}</dt><dd>{{ auth()->user()->phone ?: '—' }}</dd></div>
                <div style="border-bottom:0"><dt>{{ translate('Address') }}</dt><dd>{{ $partner->address_line }}, {{ $partner->city }}, {{ $partner->state }} {{ $partner->postal_code }}</dd></div>
            </dl>
        </div>

        <form method="POST" action="{{ route('channel_partner.onboarding.submit') }}" style="margin-top:16px">
            @csrf
            <label class="cp-check cp-card cp-card-pad">
                <input type="checkbox" name="declaration" value="1" required>
                <span>{{ translate('I confirm that the information provided is true.') }}</span>
            </label>
            @error('declaration')<div class="cp-error">{{ $message }}</div>@enderror

            <div class="cp-sticky-actions">
                <a href="{{ route('channel_partner.onboarding', ['step' => 'business']) }}" class="cp-btn cp-btn-light" style="flex:0 0 auto">
                    <x-cp-icon name="arrow-left" class="cp-icon-sm" /><span class="sr-only">{{ translate('Back') }}</span>
                </a>
                <button type="submit" class="cp-btn cp-btn-primary">{{ translate('Submit & start ordering') }}</button>
            </div>
        </form>
    @endif

    {{-- ===================== STATUS ===================== --}}
    @if ($step === 'status')
        @php
            $suspended = $partner->status === ChannelPartner::STATUS_SUSPENDED;
        @endphp
        <div class="cp-card cp-status-hero" style="margin-top:12px">
            <div class="cp-status-icon" style="background:{{ $suspended ? 'var(--cp-danger-50)' : 'var(--cp-warning-50)' }};color:{{ $suspended ? 'var(--cp-danger)' : 'var(--cp-warning)' }}">
                <x-cp-icon :name="$suspended ? 'lock' : 'clock'" class="cp-icon-lg" />
            </div>
            @if ($suspended)
                <h1>{{ translate('Account on hold') }}</h1>
                <p class="cp-muted" style="margin-top:8px">{{ $partner->rejection_reason ?: translate('Ordering is paused on your partner account. Please contact your account manager.') }}</p>
            @else
                <h1>{{ translate('Application under review') }}</h1>
                <p class="cp-muted" style="margin-top:8px">{{ translate('Thanks') }}, {{ $partner->contact_person }}! {{ translate('We are verifying your details and will email you at') }} <strong>{{ auth()->user()->email }}</strong>.</p>
                <ol class="cp-timeline">
                    <li class="done"><span class="dot"></span><strong>{{ translate('Account created') }}</strong><div class="cp-muted cp-small">{{ $partner->created_at->format('d M Y') }}</div></li>
                    <li class="done"><span class="dot"></span><strong>{{ translate('Application submitted') }}</strong><div class="cp-muted cp-small">{{ optional($partner->submitted_at)->format('d M Y, h:i A') }}</div></li>
                    <li class="current"><span class="dot"></span><strong>{{ translate('Verification & contract set-up') }}</strong><div class="cp-muted cp-small">{{ translate('Usually 1–2 working days') }}</div></li>
                    <li><span class="dot"></span><strong>{{ translate('Start ordering') }}</strong></li>
                </ol>
            @endif
            @if (get_setting('helpline_number'))
                <a href="tel:{{ get_setting('helpline_number') }}" class="cp-btn cp-btn-light cp-btn-block" style="margin-top:8px">
                    <x-cp-icon name="phone" class="cp-icon-sm" /> {{ translate('Call us') }} {{ get_setting('helpline_number') }}
                </a>
            @endif
            <a href="{{ route('channel_partner.onboarding') }}" class="cp-btn cp-btn-ghost cp-btn-block" style="margin-top:8px">
                <x-cp-icon name="refresh" class="cp-icon-sm" /> {{ translate('Refresh status') }}
            </a>
        </div>
    @endif
@endsection
