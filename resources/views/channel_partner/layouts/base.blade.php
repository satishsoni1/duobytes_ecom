<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ get_setting('base_color', '#254AA0') }}">
    <title>@yield('title', translate('Partner Portal')) · {{ get_setting('website_name') ?: config('app.name') }}</title>
    @php $cpIcon = get_setting('site_icon'); @endphp
    @if ($cpIcon)
        <link rel="icon" href="{{ uploaded_asset($cpIcon) }}">
        <link rel="apple-touch-icon" href="{{ uploaded_asset($cpIcon) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ static_asset('assets/css/channel-partner.css') }}?v={{ @filemtime(public_path('assets/css/channel-partner.css')) }}">
    <style>
        :root {
            --cp-primary: {{ get_setting('base_color', '#254AA0') }};
            --cp-primary-600: {{ get_setting('base_hov_color', '#1B3778') }};
            --cp-primary-50: {{ hex2rgba(get_setting('base_color', '#254AA0'), 0.09) }};
        }
    </style>
    @stack('head')
</head>
<body class="cp @yield('body_class')">
    @yield('body')

    <div class="cp-toast-wrap" id="cp-toasts" role="status" aria-live="polite"></div>

    <script>
        window.CP = {
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            routes: {
                quote: @json(auth()->check() ? route('channel_partner.quote') : null),
                cartStore: @json(auth()->check() ? route('channel_partner.cart.store') : null)
            },
            i18n: {
                added: @json(translate('Added to cart')),
                error: @json(translate('Something went wrong. Please try again.')),
                uploading: @json(translate('Uploading')),
                remove_confirm: @json(translate('Remove this file?')),
                offline: @json(translate('You appear to be offline. Check your connection and try again.'))
            },
            flash: @json(collect(session('flash_notification', collect())->toArray())->map(fn ($m) => ['level' => $m['level'], 'message' => strip_tags($m['message'])])->values())
        };
    </script>
    <script src="{{ static_asset('assets/js/channel-partner.js') }}?v={{ @filemtime(public_path('assets/js/channel-partner.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
