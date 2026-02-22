<turbo-frame id="tags-frame">
    <ul class="nav-tags-list">
        <li class="nav-tags-item">
            <a href="{{ $url->to('forum')->route('custom-frontend.index') }}" class="nav-tags-link" data-turbo-frame="_top">All</a>
        </li>
        @foreach ($primaryTags as $tag)
            @php
                $slug = $tag['attributes']['slug'] ?? (string) $tag['id'];
                $name = $tag['attributes']['name'] ?? $slug;
                $indexUrl = $url->to('forum')->route('custom-frontend.index') . '?tag=' . rawurlencode($slug);
            @endphp
            <li class="nav-tags-item">
                <a href="{{ $indexUrl }}" class="nav-tags-link" data-turbo-frame="_top">{{ $name }}</a>
            </li>
        @endforeach
    </ul>
</turbo-frame>
