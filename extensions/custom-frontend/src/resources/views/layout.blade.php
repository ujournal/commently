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
    @if(!empty($primaryTags ?? []) || !empty($tagsFrameUrl ?? ''))
    <nav class="nav" aria-label="Navigation">
        <button type="button" class="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="tags-frame">
            <span class="nav-toggle-icon nav-toggle-icon--menu" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </span>
            <span class="nav-toggle-icon nav-toggle-icon--close" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </span>
        </button>
        <turbo-frame id="tags-frame" data-turbo-permanent data-tags-src="{{ $tagsFrameUrl ?? '' }}" src="{{ $tagsFrameUrl ?? '' }}">
            <ul class="nav-list">
                <li class="nav-list-item">
                    <a href="{{ $url->to('forum')->route('custom-frontend.index') }}" class="nav-list-link" data-turbo-frame="_top">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M20 12L19.9988 12.1035C19.9217 16.3694 16.563 19.8241 12.3687 19.9934C12.2934 19.9976 12.2175 20 12.1412 20C12.1305 20 12.1199 19.9996 12.1093 19.9995C12.0882 19.9997 12.0671 20 12.046 20C12.0306 20 12.0153 19.9998 12 19.9997C11.9847 19.9998 11.9694 20 11.954 20C7.57635 20 4.02475 16.4226 4 12V4C8.42256 4.02475 12 7.57635 12 11.954C12 11.9694 11.9998 11.9847 11.9997 12H12.0003C12.0002 11.9847 12 11.9694 12 11.954C12 7.57635 15.5774 4.02475 20 4V12ZM5.69412 11.9905L5.69504 12.0726C5.7326 14.154 6.75308 15.9803 8.29724 17.1092C8.19578 16.757 8.14118 16.3849 8.14118 16C8.14118 14.4536 9.01873 13.1124 10.3029 12.4468L10.3056 11.9903C10.3058 11.9589 10.3059 11.9642 10.3059 11.954C10.3059 9.10434 8.3657 6.67908 5.69412 5.93153V11.9905Z" fill="#252525"/>
                        </svg>
                        <span>UJournal</span>
                    </a>
                </li>
                @foreach ($primaryTags ?? [] as $tag)
                    @php
                        $slug = $tag['attributes']['slug'] ?? (string) $tag['id'];
                        $name = $tag['attributes']['name'] ?? $slug;
                        $hasNew = !empty($tag['attributes']['hasUnread'] ?? false);
                        $indexUrl = $url->to('forum')->route('tag', ['slug' => $slug]);
                    @endphp
                    <li class="nav-list-item">
                        <a href="{{ $indexUrl }}" class="nav-list-link {{ $hasNew ? 'nav-list-link--has-new' : '' }}" data-turbo-frame="_top">
                            <span>{{ $name }}</span>
                            @if ($hasNew)
                                <span class="nav-list-dot" aria-hidden="true" title="{{ $translator->trans('commently-custom-frontend.tags.has_new') }}"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </turbo-frame>
        <turbo-frame id="profile-frame" data-turbo-permanent>
            @if(isset($actor) && !$actor->isGuest() && isset($profileUrl) && $profileUrl)
            <ul class="nav-list">
                <li class="nav-list-item">
                    <a href="{{ $profileUrl }}" class="nav-list-link" data-turbo-frame="_top">
                        @if(!empty($actorAvatarUrl ?? ''))
                            <img src="{{ $actorAvatarUrl }}" alt="" class="nav-list-avatar" width="24" height="24" loading="lazy">
                        @else
                            <span class="nav-list-avatar nav-list-avatar--placeholder" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
                                </svg>
                            </span>
                        @endif
                        <span class="nav-list-link-text">{{ $actor->getAttribute('display_name') ?? $actor->display_name ?? $actor->getAttribute('username') ?? $actor->username ?? '' }}</span>
                    </a>
                </li>
            </ul>
            @endif
        </turbo-frame>
    </nav>
    @endif
    <main id="main">
        @yield('content')
    </main>
    <script src="{{ $customFrontendAsset('js/main.js') }}"></script>
    <script>
    (function () {
        var frame = document.getElementById('tags-frame');
        var tagsSrc = frame && frame.getAttribute('data-tags-src');
        if (!frame || !tagsSrc) return;
        function reloadTags() {
            frame.src = tagsSrc;
        }
        document.documentElement.addEventListener('turbo:load', reloadTags);
    })();
    </script>
    @stack('scripts')
</body>
</html>
