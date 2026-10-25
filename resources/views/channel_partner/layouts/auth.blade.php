@extends('channel_partner.layouts.base')

@section('body')
    <div class="cp-auth">
        <div class="cp-auth-top">
            <a href="{{ route('home') }}" class="cp-brand" aria-label="{{ translate('Back to store') }}">
                @if (get_setting('header_logo'))
                    <img src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="{{ get_setting('website_name') }}">
                @else
                    <strong>{{ get_setting('website_name') ?: config('app.name') }}</strong>
                @endif
                <span class="cp-brand-tag">{{ translate('Partner') }}</span>
            </a>
            @auth
                <form method="POST" action="{{ route('channel_partner.logout') }}">
                    @csrf
                    <button type="submit" class="cp-btn cp-btn-ghost cp-btn-sm">{{ translate('Log out') }}</button>
                </form>
            @else
                @yield('top_action')
            @endauth
        </div>
        <div class="cp-auth-body @yield('auth_width')">
            @yield('content')
        </div>
    </div>
@endsection
