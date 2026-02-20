<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Custom Frontend')</title>
    <link rel="stylesheet" href="{{ $customFrontendAsset('css/main.css') }}">
    @stack('styles')
    <script src="{{ $customFrontendAsset('js/turbo.js') }}" defer></script>
</head>
<body data-turbo="true">
    <main id="main">
        @yield('content')
    </main>
    <script src="{{ $customFrontendAsset('js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
