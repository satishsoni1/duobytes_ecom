@extends('channel_partner.layouts.auth')

@section('title', translate('Partner registration'))

@section('top_action')
    <a href="{{ route('channel_partner.login') }}" class="cp-btn cp-btn-ghost cp-btn-sm">{{ translate('Log in') }}</a>
@endsection

@section('content')
    @include('channel_partner.onboarding.partials.steps', ['current' => 1])

    <h1 class="cp-auth-title">{{ translate('Become a channel partner') }}</h1>
    <p class="cp-auth-sub">{{ translate('Create your account. You can add business details and documents next.') }}</p>

    @if ($errors->any())
        <div class="cp-alert cp-alert-danger" style="margin-bottom:16px" role="alert">
            <x-cp-icon name="alert" class="cp-icon-sm" />
            <div>{{ translate('Please check the highlighted fields.') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('channel_partner.register.store') }}" novalidate>
        @csrf
        <div class="cp-field">
            <label class="cp-label" for="name">{{ translate('Your full name') }} <span class="req">*</span></label>
            <input id="name" name="name" type="text" class="cp-input @error('name') is-invalid @enderror" value="{{ old('name') }}" autocomplete="name" required maxlength="100">
            @error('name')<div class="cp-error">{{ $message }}</div>@enderror
        </div>

        <div class="cp-field">
            <label class="cp-label" for="company_name">{{ translate('Business / company name') }} <span class="req">*</span></label>
            <input id="company_name" name="company_name" type="text" class="cp-input @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" autocomplete="organization" required maxlength="150">
            @error('company_name')<div class="cp-error">{{ $message }}</div>@enderror
        </div>

        <div class="cp-field">
            <label class="cp-label" for="phone">{{ translate('Mobile number') }} <span class="req">*</span></label>
            <input id="phone" name="phone" type="tel" inputmode="tel" class="cp-input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" autocomplete="tel" placeholder="+91 98765 43210" required>
            @error('phone')<div class="cp-error">{{ $message }}</div>@enderror
        </div>

        <div class="cp-field">
            <label class="cp-label" for="email">{{ translate('Work email') }} <span class="req">*</span></label>
            <input id="email" name="email" type="email" inputmode="email" class="cp-input @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email" autocapitalize="off" required>
            @error('email')<div class="cp-error">{{ $message }}</div>@enderror
        </div>

        <div class="cp-field">
            <label class="cp-label" for="password">{{ translate('Create password') }} <span class="req">*</span></label>
            <div class="cp-input-group">
                <input id="password" name="password" type="password" class="cp-input @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>
                <button type="button" class="cp-icon-btn cp-input-addon" data-cp-toggle-password aria-label="{{ translate('Show password') }}" aria-pressed="false">
                    <x-cp-icon name="eye" /><x-cp-icon name="eye-off" style="display:none" />
                </button>
            </div>
            <div class="cp-hint">{{ translate('At least 8 characters with letters and numbers.') }}</div>
            @error('password')<div class="cp-error">{{ $message }}</div>@enderror
        </div>

        <div class="cp-field">
            <label class="cp-check">
                <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                <span>{{ translate('I agree to the') }}
                    <a href="{{ route('terms') }}" target="_blank" rel="noopener">{{ translate('terms & conditions') }}</a>
                    {{ translate('and') }}
                    <a href="{{ route('privacypolicy') }}" target="_blank" rel="noopener">{{ translate('privacy policy') }}</a>.
                </span>
            </label>
            @error('terms')<div class="cp-error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="cp-btn cp-btn-primary cp-btn-block">{{ translate('Create account & continue') }}</button>
        <p class="cp-muted cp-small" style="text-align:center;margin-top:16px">
            {{ translate('Already registered?') }} <a href="{{ route('channel_partner.login') }}">{{ translate('Log in') }}</a>
        </p>
    </form>
@endsection
