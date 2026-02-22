<!DOCTYPE html>
<html lang="{{ isset($translator) ? $translator->getLocale() : 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', isset($translator) ? $translator->trans('commently-custom-frontend.layout.default_title') : 'Custom Frontend')</title>
    <link rel="stylesheet" href="{{ $customFrontendAsset('css/main.css') }}">
    @stack('styles')
    <script src="{{ $customFrontendAsset('js/turbo.js') }}" defer></script>
</head>
<body data-turbo="true">
    @if(!empty($tagsFrameUrl ?? ''))
    <nav class="nav-tags" aria-label="Tags">
        <turbo-frame id="tags-frame" src="{{ $tagsFrameUrl }}" loading="lazy" data-turbo-permanent>
            <span class="nav-tags-loading" aria-hidden="true">…</span>
        </turbo-frame>
    </nav>
    @endif
    <main id="main">
        @yield('content')
    </main>
    <script src="{{ $customFrontendAsset('js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
