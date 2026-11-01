@extends('frontend.layouts.app')

@section('meta_title'){{ translate('About Us') }} | {{ get_setting('website_name') }}@stop

@section('meta_description'){{ translate('ToYeZa is a manufacturer and supplier of cleaning solutions for industrial, commercial, institutional, and household needs.') }}@stop

@section('meta')
    <link rel="stylesheet" href="{{ static_asset('assets/css/home-modern.css') }}?v={{ @filemtime(public_path('assets/css/home-modern.css')) }}">
    <meta property="og:title" content="{{ translate('About Us') }} | {{ get_setting('website_name') }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ route('about-us') }}" />
    <meta property="og:description" content="{{ translate('ToYeZa is a manufacturer and supplier of cleaning solutions for industrial, commercial, institutional, and household needs.') }}" />
    <style>
        .tz-about-hero { border-radius: var(--tz-radius); padding: 32px 22px; color: #fff;
            background: radial-gradient(90% 120% at 100% 0%, rgba(255, 255, 255, .22), rgba(255, 255, 255, 0) 60%), radial-gradient(70% 90% at 0% 100%, rgba(0, 0, 0, .25), rgba(0, 0, 0, 0) 60%), var(--primary); }
        .tz-about-hero h1 { color: #fff; font-size: 30px; font-weight: 800; letter-spacing: -.02em; margin: 12px 0 10px; }
        .tz-about-hero p { font-size: 16px; opacity: .92; max-width: 60ch; margin: 0; }
        .tz-about-crumbs { font-size: 13px; opacity: .85; }
        .tz-about-crumbs a { color: #fff !important; }
        .tz-about-grid { display: grid; gap: 16px; }
        .tz-about-card { border: 1px solid var(--tz-line); border-radius: var(--tz-radius); padding: 22px; background: #fff; }
        .tz-about-card h2 { font-size: 18px; font-weight: 800; margin: 0 0 10px; color: var(--tz-ink); }
        .tz-about-card p { font-size: 15px; line-height: 1.7; color: var(--tz-ink-2); margin: 0; }
        .tz-about-list { list-style: none; padding: 0; margin: 14px 0 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; }
        .tz-about-list li { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: var(--tz-radius-sm); background: var(--tz-bg); font-size: 14px; font-weight: 600; color: var(--tz-ink); }
        .tz-about-list i { font-size: 20px; color: var(--primary); }
        .tz-about-tagline { text-align: center; font-size: 20px; font-weight: 800; letter-spacing: -.01em; color: var(--tz-ink); margin: 0 0 16px; }
        .tz-about-ctas { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        @media (min-width: 992px) {
            .tz-about-hero { padding: 52px 56px; }
            .tz-about-hero h1 { font-size: 44px; }
            .tz-about-grid { grid-template-columns: 1fr 1fr; gap: 20px; }
            .tz-about-grid .wide { grid-column: 1 / -1; }
            .tz-about-tagline { font-size: 26px; }
        }
    </style>
@endsection

@section('content')
<div class="tz-home">
    <section class="tz-section">
        <div class="tz-wrap">
            <div class="tz-about-hero">
                <div class="tz-about-crumbs">
                    <a href="{{ route('home') }}">{{ translate('Home') }}</a> / {{ translate('About Us') }}
                </div>
                <h1>{{ translate('About Us') }}</h1>
                <p>{{ translate('ToYeZa is a manufacturer and supplier of cleaning solutions for industrial, commercial, institutional, and household needs.') }}</p>
            </div>
        </div>
    </section>

    <section class="tz-section pt-0">
        <div class="tz-wrap">
            <div class="tz-about-grid">
                <div class="tz-about-card wide">
                    <h2>{{ translate('What We Offer') }}</h2>
                    <p>{{ translate('We offer a wide range of products, including industrial degreasers, rust cleaners, maintenance chemicals, and everyday cleaning products. Our solutions cater to different sectors such as manufacturing units, factories, railway workshops, hospitals, hotels, offices, automobile industries, homes and more.') }}</p>
                    <ul class="tz-about-list">
                        <li><i class="las la-oil-can"></i>{{ translate('Industrial degreasers') }}</li>
                        <li><i class="las la-tools"></i>{{ translate('Rust cleaners') }}</li>
                        <li><i class="las la-flask"></i>{{ translate('Maintenance chemicals') }}</li>
                        <li><i class="las la-spray-can"></i>{{ translate('Everyday cleaning products') }}</li>
                    </ul>
                </div>

                <div class="tz-about-card wide">
                    <h2>{{ translate('Sectors We Serve') }}</h2>
                    <ul class="tz-about-list mt-0">
                        <li><i class="las la-industry"></i>{{ translate('Manufacturing units') }}</li>
                        <li><i class="las la-warehouse"></i>{{ translate('Factories') }}</li>
                        <li><i class="las la-train"></i>{{ translate('Railway workshops') }}</li>
                        <li><i class="las la-hospital"></i>{{ translate('Hospitals') }}</li>
                        <li><i class="las la-hotel"></i>{{ translate('Hotels') }}</li>
                        <li><i class="las la-building"></i>{{ translate('Offices') }}</li>
                        <li><i class="las la-car"></i>{{ translate('Automobile industries') }}</li>
                        <li><i class="las la-home"></i>{{ translate('Homes') }}</li>
                    </ul>
                </div>

                <div class="tz-about-card">
                    <h2>{{ translate('Our Approach') }}</h2>
                    <p>{{ translate('We understand that every cleaning requirement is different. Our aim is to provide effective, reliable, and easy-to-use products that make cleaning and maintenance simpler.') }}</p>
                </div>

                <div class="tz-about-card">
                    <h2>{{ translate('Our Commitment') }}</h2>
                    <p>{{ translate('At ToYeZa, we focus on product quality, customer satisfaction, and building long-term relationships by providing the right cleaning solutions for every need.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="tz-section tz-section-tint">
        <div class="tz-wrap">
            <p class="tz-about-tagline">ToYeZa – {{ translate('Powerful Cleaning Solutions For Every Surface!') }}</p>
            <div class="tz-about-ctas">
                <a href="{{ route('search') }}" class="tz-btn tz-btn-primary">{{ translate('Browse Products') }}</a>
                <a href="{{ route('custom-pages.show_custom_page', 'contact-us') }}" class="tz-btn tz-btn-light border">{{ translate('Contact Us') }}</a>
            </div>
        </div>
    </section>
</div>
@endsection
