@extends('channel_partner.layouts.auth')

@section('title', translate('Partner login'))

@section('top_action')
    <a href="{{ route('channel_partner.register') }}" class="cp-btn cp-btn-ghost cp-btn-sm">{{ translate('Become a partner') }}</a>
@endsection

@section('content')
    <div style="padding-top:24px">
        <span class="cp-doc-icon" style="margin-bottom:16px"><x-cp-icon name="briefcase" /></span>
        <h1 class="cp-auth-title">{{ translate('Partner login') }}</h1>
        <p class="cp-auth-sub">{{ translate('Log in to order at your contract prices.') }}</p>
    </div>

    <form method="POST" action="{{ route('channel_partner.login.attempt') }}" novalidate>
        @csrf
        @error('login')
            <div class="cp-alert cp-alert-danger" style="margin-bottom:16px" role="alert">
                <x-cp-icon name="alert" class="cp-icon-sm" /><div>{{ $message }}</div>
            </div>
        @enderror

        <div class="cp-field">
            <label class="cp-label" for="login">{{ translate('Email or mobile number') }}</label>
            <input id="login" name="login" type="text" class="cp-input @error('login') is-invalid @enderror" value="{{ old('login') }}" autocomplete="username" autocapitalize="off" spellcheck="false" required autofocus>
        </div>

        <div class="cp-field">
            <div class="cp-between">
                <label class="cp-label" for="password">{{ translate('Password') }}</label>
                <a href="{{ route('password.request') }}" class="cp-small" style="margin-bottom:6px">{{ translate('Forgot password?') }}</a>
            </div>
            <div class="cp-input-group">
                <input id="password" name="password" type="password" class="cp-input" autocomplete="current-password" required>
                <button type="button" class="cp-icon-btn cp-input-addon" data-cp-toggle-password aria-label="{{ translate('Show password') }}" aria-pressed="false">
                    <x-cp-icon name="eye" /><x-cp-icon name="eye-off" style="display:none" />
                </button>
            </div>
        </div>

        <div class="cp-field">
            <label class="cp-check"><input type="checkbox" name="remember" value="1" checked> <span>{{ translate('Keep me logged in on this device') }}</span></label>
        </div>

        <button type="submit" class="cp-btn cp-btn-primary cp-btn-block">{{ translate('Log in') }}</button>

        <div class="cp-card cp-card-pad" style="margin-top:24px;text-align:center">
            <strong>{{ translate('New to the partner program?') }}</strong>
            <p class="cp-muted cp-small" style="margin:4px 0 12px">{{ translate('Apply in a few minutes from your phone.') }}</p>
            <a href="{{ route('channel_partner.register') }}" class="cp-btn cp-btn-light cp-btn-block">{{ translate('Apply to become a partner') }}</a>
        </div>
    </form>
@endsection
