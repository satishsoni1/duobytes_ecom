@extends('channel_partner.layouts.auth')

@section('title', translate('Become a Channel Partner'))
@section('auth_width', 'wide')

@section('top_action')
    <a href="{{ route('channel_partner.login') }}" class="cp-btn cp-btn-light cp-btn-sm">{{ translate('Partner login') }}</a>
@endsection

@section('content')
    <section class="cp-hero" style="padding:28px 22px">
        <span class="cp-pill" style="background:rgba(255,255,255,.18);color:#fff">{{ translate('Channel Partner Program') }}</span>
        <h1 style="font-size:28px;margin-top:12px">{{ translate('Grow your business with') }} {{ get_setting('website_name') ?: translate('us') }}</h1>
        <div class="cp-row" style="margin-top:20px;flex-wrap:wrap">
            <a href="{{ route('channel_partner.register') }}" class="cp-btn" style="background:#fff;color:var(--cp-primary) !important">{{ translate('Apply now') }} <x-cp-icon name="arrow-right" class="cp-icon-sm" /></a>
            <a href="{{ route('channel_partner.login') }}" class="cp-btn" style="color:#fff !important;border-color:rgba(255,255,255,.4)">{{ translate('I already have an account') }}</a>
        </div>
    </section>

    <section class="cp-card cp-card-pad" style="margin-top:16px">
        <h2>{{ translate('How it works') }}</h2>
        <ol class="cp-timeline" style="margin-top:16px">
            <li class="done"><span class="dot"></span><strong>{{ translate('Create your account') }}</strong><div class="cp-muted cp-small">{{ translate('Takes about a minute.') }}</div></li>
            <li class="done"><span class="dot"></span><strong>{{ translate('Add business details & documents') }}</strong><div class="cp-muted cp-small">{{ translate('Snap photos with your phone or upload PDFs.') }}</div></li>
            <li class="done"><span class="dot"></span><strong>{{ translate('Start ordering') }}</strong><div class="cp-muted cp-small">{{ translate('Browse the catalogue at your prices and place orders.') }}</div></li>
        </ol>
    </section>

    <section class="cp-card cp-card-pad" style="margin-top:16px">
        <h2>{{ translate('Keep these documents ready') }}</h2>
        <ul class="cp-list" style="margin-top:8px">
            @foreach (config('channel_partner.document_types') as $doc)
                <li class="cp-row" style="padding:10px 0">
                    <x-cp-icon :name="$doc['required'] ? 'check-circle' : 'file'" class="cp-icon-sm" style="color:{{ $doc['required'] ? 'var(--cp-success)' : 'var(--cp-ink-3)' }}" />
                    <span>{{ translate($doc['label']) }}</span>
                    <span class="cp-muted cp-small" style="margin-left:auto">{{ $doc['required'] ? translate('Required') : translate('Optional') }}</span>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('channel_partner.register') }}" class="cp-btn cp-btn-primary cp-btn-block" style="margin-top:16px">{{ translate('Start application') }}</a>
    </section>
@endsection
