<turbo-frame id="tags-frame">
    <ul class="nav-tags-list">
        <li class="nav-tags-item">
            <a href="{{ $url->to('forum')->route('custom-frontend.index') }}?tag=" class="nav-tags-link" data-turbo-frame="_top">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M20 12L19.9988 12.1035C19.9217 16.3694 16.563 19.8241 12.3687 19.9934C12.2934 19.9976 12.2175 20 12.1412 20C12.1305 20 12.1199 19.9996 12.1093 19.9995C12.0882 19.9997 12.0671 20 12.046 20C12.0306 20 12.0153 19.9998 12 19.9997C11.9847 19.9998 11.9694 20 11.954 20C7.57635 20 4.02475 16.4226 4 12V4C8.42256 4.02475 12 7.57635 12 11.954C12 11.9694 11.9998 11.9847 11.9997 12H12.0003C12.0002 11.9847 12 11.9694 12 11.954C12 7.57635 15.5774 4.02475 20 4V12ZM5.69412 11.9905L5.69504 12.0726C5.7326 14.154 6.75308 15.9803 8.29724 17.1092C8.19578 16.757 8.14118 16.3849 8.14118 16C8.14118 14.4536 9.01873 13.1124 10.3029 12.4468L10.3056 11.9903C10.3058 11.9589 10.3059 11.9642 10.3059 11.954C10.3059 9.10434 8.3657 6.67908 5.69412 5.93153V11.9905Z" fill="#252525"/>
                </svg>
                <span>UJournal</span>
            </a>
        </li>
        @foreach ($primaryTags as $tag)
            @php
                $slug = $tag['attributes']['slug'] ?? (string) $tag['id'];
                $name = $tag['attributes']['name'] ?? $slug;
                $hasNew = !empty($tag['attributes']['hasUnread'] ?? false);
                $indexUrl = $url->to('forum')->route('custom-frontend.index') . '?tag=' . rawurlencode($slug);
            @endphp
            <li class="nav-tags-item">
                <a href="{{ $indexUrl }}" class="nav-tags-link {{ $hasNew ? 'nav-tags-link--has-new' : '' }}" data-turbo-frame="_top">
                    <span>{{ $name }}</span>
                    @if ($hasNew)
                        <span class="nav-tags-dot" aria-hidden="true" title="{{ $translator->trans('commently-custom-frontend.tags.has_new') }}"></span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</turbo-frame>
