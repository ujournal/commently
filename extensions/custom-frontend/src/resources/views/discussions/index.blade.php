@extends('custom-frontend::layout')

@section('title', 'Discussions')

@section('content')
    <h1>Discussions</h1>

    @if (count($primaryTags ?? []) > 0)
        <div class="discussion-tag-filters">
            <form method="post" action="{{ $url->to('forum')->route('custom-frontend.discussions.tag-subscriptions') }}" class="discussion-tag-filters-form" data-turbo="true">
                <input type="hidden" name="csrfToken" value="{{ $csrfToken ?? '' }}">
                <span class="discussion-tag-filters-label">Show topics in:</span>
                <div class="discussion-tag-filters-list">
                    @foreach ($primaryTags as $tag)
                        @php
                            $slug = $tag['attributes']['slug'] ?? (string) $tag['id'];
                            $name = $tag['attributes']['name'] ?? $slug;
                            $isChecked = in_array($slug, $filterSlugs ?? [], true);
                        @endphp
                        <label class="discussion-tag-filter-item">
                            <input type="checkbox" name="tag_slugs[]" value="{{ $slug }}" {{ $isChecked ? 'checked' : '' }}>
                            <span class="discussion-tag-filter-name">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </form>
            <script>
                (function () {
                    var form = document.querySelector('.discussion-tag-filters-form');
                    if (form) {
                        form.addEventListener('change', function () {
                            form.submit();
                        });
                    }
                })();
            </script>
            @if (!($subscriptionApiAvailable ?? false) && count($filterSlugs ?? []) > 0)
                <p class="discussion-tag-filters-hint">Filter is applied for this visit. Enable the Tag Subscriptions extension to save your preferred topics.</p>
            @endif
        </div>
    @endif

    <p><a href="{{ $url->to('forum')->route('custom-frontend.discussions.create.page') }}" data-turbo="true">Start a discussion</a></p>

    @if (isset($apiDocument->data) && count($apiDocument->data) > 0)
        <ul class="discussion-list">
            @foreach ($apiDocument->data as $discussion)
                @php
                    $thumbnailUrl = $discussion->attributes->customThumbnail ?? null;
                    $thumbnailWidth = $discussion->attributes->customThumbnailWidth ?? null;
                    $thumbnailHeight = $discussion->attributes->customThumbnailHeight ?? null;
                    $userLink = $discussion->relationships->user->data ?? null;
                    $user = $userLink ? $getResource($userLink) : null;
                    $tagLinks = isset($discussion->relationships->tags->data) ? (array) $discussion->relationships->tags->data : [];
                    $tags = array_filter(array_map(fn ($link) => $getResource($link), $tagLinks));
                    $excerpt = isset($getDiscussionExcerpt) ? $getDiscussionExcerpt($discussion) : null;
                @endphp
                <li class="discussion-item">
                    <a href="{{ $url->to('forum')->route('custom-frontend.discussion', ['id' => $discussion->id]) }}" data-turbo="true" class="discussion-item-link">
                        @if ($thumbnailUrl)
                            <div class="discussion-item-thumbnail-container">
                                <img src="{{ $thumbnailUrl }}" alt="" class="discussion-item-thumbnail" loading="lazy"
                                    @if ($thumbnailWidth) width="{{ $thumbnailWidth }}" @endif
                                    @if ($thumbnailHeight) height="{{ $thumbnailHeight }}" @endif>
                            </div>
                        @endif
                        <div class="discussion-item-body">
                            <h2 class="discussion-item-title">{{ $discussion->attributes->title ?? 'Discussion #' . $discussion->id }}</h2>
                            @if ($excerpt)
                                <p class="discussion-item-excerpt">{{ $excerpt }}</p>
                            @endif
                            <div class="discussion-item-meta">
                                @if ($user)
                                    <span class="discussion-item-user">{{ $user->attributes->displayName ?? $user->attributes->username ?? '' }}</span>
                                @endif
                                @if (count($tags) > 0)
                                    <span class="discussion-item-tags">
                                        @foreach ($tags as $tag)
                                            <span class="discussion-item-tag">{{ $tag->attributes->name ?? '' }}</span>
                                            @if (!$loop->last)<span class="discussion-item-tag-sep">, </span>@endif
                                        @endforeach
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($page > 1)
            <a href="{{ $url->to('forum')->route('custom-frontend.index') }}?page={{ $page - 1 }}">&laquo; Previous</a>
        @endif
        @if (!empty($hasNextPage))
            <a href="{{ $url->to('forum')->route('custom-frontend.index') }}?page={{ $page + 1 }}">Next &raquo;</a>
        @endif
    @else
        <p>No discussions yet.</p>
    @endif
@endsection
