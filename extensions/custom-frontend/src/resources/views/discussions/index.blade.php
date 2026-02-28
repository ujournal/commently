@extends('custom-frontend::layout')

@section('title', isset($activeTagName) ? $activeTagName . ' – ' . $translator->trans('commently-custom-frontend.discussions.title') : $translator->trans('commently-custom-frontend.discussions.title'))

@section('content')
    @php
        $listBaseUrl = (isset($filterSlugs) && count($filterSlugs) === 1)
            ? $url->to('forum')->route('tag', ['slug' => $filterSlugs[0]])
            : $url->to('forum')->route('custom-frontend.index');
        $listUrl = function ($page = 1, $sortVal = null) use ($listBaseUrl, $sort) {
            $s = $sortVal !== null ? $sortVal : $sort;
            $q = $page > 1 ? ['page' => $page] : [];
            if ($s !== null && $s !== '' && $s !== 'latest') {
                $q['sort'] = $s;
            }
            return $q === [] ? $listBaseUrl : $listBaseUrl . '?' . http_build_query($q);
        };
        $sortOptions = [
            'latest' => 'commently-custom-frontend.discussions.sort_latest',
            'top' => 'commently-custom-frontend.discussions.sort_top',
            'likes' => 'commently-custom-frontend.discussions.sort_likes',
            'hot' => 'commently-custom-frontend.discussions.sort_hot',
            'newest' => 'commently-custom-frontend.discussions.sort_newest',
            'oldest' => 'commently-custom-frontend.discussions.sort_oldest',
        ];
    @endphp
    <div class="discussions-header">
        <h1 class="discussions-title">{{ isset($activeTagName) ? $activeTagName : $translator->trans('commently-custom-frontend.discussions.title') }}</h1>
        <div class="discussions-header-actions">
            <button type="button" class="discussions-filter-btn" aria-label="{{ $translator->trans('commently-custom-frontend.discussions.toggle_filters') }}" aria-expanded="false" aria-controls="discussion-tag-filters" title="{{ $translator->trans('commently-custom-frontend.discussions.toggle_filters') }}">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M400-240v-80h160v80H400ZM240-440v-80h480v80H240ZM120-640v-80h720v80H120Z"/></svg>
            </button>
            <a href="{{ $url->to('forum')->route('custom-frontend.discussions.create.page') }}" class="discussions-start-btn" id="discussions-header-write" data-turbo="true">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/></svg>
                <span>{{ $translator->trans('commently-custom-frontend.discussions.write') }}</span>
            </a>
        </div>
    </div>

    <a href="{{ $url->to('forum')->route('custom-frontend.discussions.create.page') }}" class="discussions-start-btn discussions-start-btn-floating" id="discussions-write-floating" aria-label="{{ $translator->trans('commently-custom-frontend.discussions.write') }}" data-turbo="true" hidden>
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/></svg>
    </a>

    <div class="discussion-tag-filters discussion-tag-filters-is-hidden" id="discussion-tag-filters">
        <div class="discussion-filters-section" role="group" aria-label="{{ $translator->trans('commently-custom-frontend.discussions.sort_by') }}">
            <span class="discussion-filters-section-label">{{ $translator->trans('commently-custom-frontend.discussions.sort_by') }}</span>
            <div class="discussions-sort">
                @foreach ($sortOptions as $key => $labelKey)
                    @if (isset($sortMap[$key]))
                        <a href="{{ $listUrl(1, $key) }}" class="discussions-sort-link {{ ($sort ?? 'latest') === $key ? 'discussions-sort-link--active' : '' }}" data-turbo="true">{{ $translator->trans($labelKey) }}</a>
                    @endif
                @endforeach
            </div>
        </div>
        @if (count($primaryTags ?? []) > 0)
            <form method="post" action="{{ $url->to('forum')->route('custom-frontend.discussions.tag-subscriptions') }}" class="discussion-tag-filters-form" data-turbo="true">
                <input type="hidden" name="csrfToken" value="{{ $csrfToken ?? '' }}">
                <div class="discussion-filters-section">
                    <span class="discussion-filters-section-label">{{ $translator->trans('commently-custom-frontend.discussions.filter_by_tag') }}</span>
                    <div class="discussion-tag-filters-list">
                        @foreach ($primaryTags as $tag)
                            @php
                                $slug = $tag['attributes']['slug'] ?? (string) $tag['id'];
                                $name = $tag['attributes']['name'] ?? $slug;
                                $slugsForCheckboxes = count($filterSlugs ?? []) > 0 ? ($filterSlugs ?? []) : ($subscribedTagSlugs ?? []);
                                $isChecked = in_array($slug, $slugsForCheckboxes, true);
                            @endphp
                            <label class="discussion-tag-filter-item">
                                <input type="checkbox" name="tag_slugs[]" value="{{ $slug }}" {{ $isChecked ? 'checked' : '' }}>
                                <span class="discussion-tag-filter-name">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
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
                <p class="discussion-tag-filters-hint">{{ $translator->trans('commently-custom-frontend.discussions.filter_hint') }}</p>
            @endif
        @endif
    </div>
    <script>
        (function () {
            var btn = document.querySelector('.discussions-filter-btn');
            var block = document.getElementById('discussion-tag-filters');
            if (btn && block) {
                btn.addEventListener('click', function () {
                    var hidden = block.classList.toggle('discussion-tag-filters-is-hidden');
                    btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
                });
            }
        })();
    </script>

    @if (isset($apiDocument->data) && count($apiDocument->data) > 0)
        <div data-turbo-prefetch="false">
        <ul class="discussion-list">
            @foreach ($apiDocument->data as $discussion)
                @php
                    $thumbnailUrl = $discussion->attributes->customThumbnail ?? null;
                    $thumbnailWidth = $discussion->attributes->customThumbnailWidth ?? null;
                    $thumbnailHeight = $discussion->attributes->customThumbnailHeight ?? null;
                    $thumbAspect = ($thumbnailWidth && $thumbnailHeight && (float) $thumbnailHeight > 0)
                        ? (float) $thumbnailWidth / (float) $thumbnailHeight
                        : null;
                    $isWideThumb = $thumbAspect !== null && $thumbAspect >= 1.5 && $thumbAspect <= 2.1; // square through 1.5 (3:2)
                    $isTallThumb = $thumbAspect !== null && $thumbAspect <= 1; // vertical or square
                    $userLink = $discussion->relationships->user->data ?? null;
                    $user = $userLink ? $getResource($userLink) : null;
                    $tagLinks = isset($discussion->relationships->tags->data) ? (array) $discussion->relationships->tags->data : [];
                    $tags = array_filter(array_map(fn ($link) => $getResource($link), $tagLinks));
                    $excerpt = isset($getDiscussionExcerpt) ? $getDiscussionExcerpt($discussion) : null;
                    $isUnread = in_array((string) ($discussion->id ?? ''), $unreadDiscussionIds ?? [], true);
                @endphp
                <li class="discussion-item discussion-item--{{ $isUnread ? 'unread' : 'read' }}">
                    <div class="discussion-item-body">
                        <div class="discussion-item-header">
                            <span class="discussion-item-header-left">
                                @if ($user)
                                    @if(!empty($user->attributes->avatarUrl ?? ''))
                                        <img src="{{ $user->attributes->avatarUrl }}" alt="" class="discussion-item-user-avatar" loading="lazy">
                                    @else
                                        <span class="discussion-item-user-avatar discussion-item-user-avatar--placeholder" aria-hidden="true">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
                                            </svg>
                                        </span>
                                    @endif
                                    <span class="discussion-item-user">
                                        {{ $user->attributes->displayName ?? $user->attributes->username ?? '' }}
                                    </span>
                                @endif
                                @if (!empty($discussion->attributes->createdAt))
                                    @php
                                        $createdAt = \Carbon\Carbon::parse($discussion->attributes->createdAt);
                                        $dateLocale = $locale ?? 'en';
                                        $createdAtLocalized = $createdAt->locale($dateLocale);
                                        $lessThan5MinAgo = $createdAt->gt(\Carbon\Carbon::now()->subMinutes(5));
                                        $lessThanHourAgo = $createdAt->gt(\Carbon\Carbon::now()->subHour());
                                    @endphp
                                    <span class="discussion-item-header-middot"> · </span>
                                    <span class="discussion-item-date">
                                        @if ($lessThan5MinAgo)
                                            {{ $translator->trans('commently-custom-frontend.discussions.now') }}
                                        @elseif ($lessThanHourAgo)
                                            {{ $createdAtLocalized->diffForHumans() }}
                                        @else
                                            @php
                                                $olderThanYear = $createdAt->lt(\Carbon\Carbon::now()->subYear());
                                                if ($olderThanYear) {
                                                    $dateFmt = \IntlDateFormatter::create(
                                                        $dateLocale,
                                                        \IntlDateFormatter::SHORT,
                                                        \IntlDateFormatter::NONE
                                                    );
                                                    if ($dateFmt) {
                                                        $dateFmt->setTimezone($createdAt->getTimezone());
                                                        $displayDate = $dateFmt->format($createdAt->getTimestamp());
                                                    } else {
                                                        $displayDate = $createdAtLocalized->translatedFormat('d.m.Y');
                                                    }
                                                } else {
                                                    $displayDate = $createdAtLocalized->translatedFormat('j F');
                                                }
                                            @endphp
                                            {{ $displayDate }}
                                        @endif
                                    </span>
                                @endif
                            </span>
                            <span class="discussion-item-header-right">
                                @if (count($tags) > 0)
                                    <span class="discussion-item-tags">
                                        @foreach ($tags as $tag)
                                            <span class="discussion-item-tag">{{ $tag->attributes->name ?? '' }}</span>
                                            @if (!$loop->last)<span class="discussion-item-tag-sep">, </span>@endif
                                        @endforeach
                                    </span>
                                @endif
                            </span>
                        </div>
                        <a href="{{ $url->to('forum')->route('custom-frontend.discussion', ['id' => $discussion->id]) }}" data-turbo="true" class="discussion-item-link">
                            <div class="discussion-item-body-inner">
                                <h2 class="discussion-item-title">{{ $discussion->attributes->title ?? $translator->trans('commently-custom-frontend.discussions.discussion_number', ['id' => $discussion->id]) }}</h2>
                                @if ($excerpt)
                                    <p class="discussion-item-excerpt">{{ $excerpt }}</p>
                                @endif
                            </div>
                            @if ($thumbnailUrl)
                                <div class="discussion-item-thumbnail-container {{ $isWideThumb ? 'discussion-item-thumbnail-container--wide' : '' }} {{ $isTallThumb ? 'discussion-item-thumbnail-container--tall' : '' }}" style="--thumb-url: url('{{ str_replace("'", "\\'", e($thumbnailUrl)) }}')">
                                    <img src="{{ $thumbnailUrl }}" alt="" class="discussion-item-thumbnail" loading="lazy"
                                        @if ($thumbnailWidth) width="{{ $thumbnailWidth }}" @endif
                                        @if ($thumbnailHeight) height="{{ $thumbnailHeight }}" @endif>
                                </div>
                            @endif
                        </a>
                        <div class="discussion-item-meta">
                            <span class="discussion-item-meta-counts">
                                @php
                                    $replyCount = max(0, (int) ($discussion->attributes->commentCount ?? 0) - 1);
                                    $reactionCount = (int) ($discussion->attributes->likeCount ?? 0);
                                @endphp
                                <span class="discussion-item-meta-count discussion-item-comments-count">
                                    <svg class="discussion-item-meta-icon" viewBox="0 -960 960 960" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M880-80 720-240H320q-33 0-56.5-23.5T240-320v-40h440q33 0 56.5-23.5T760-440v-280h40q33 0 56.5 23.5T880-640v560ZM160-473l47-47h393v-280H160v327ZM80-280v-520q0-33 23.5-56.5T160-880h440q33 0 56.5 23.5T680-800v280q0 33-23.5 56.5T600-440H240L80-280Zm80-240v-280 280Z"/></svg>
                                    <span>{{ $replyCount === 0 ? $translator->trans('commently-custom-frontend.discussions.reply') : $translator->trans('commently-custom-frontend.discussions.reply_with_count', ['count' => $replyCount]) }}</span>
                                </span>
                                <span class="discussion-item-meta-count discussion-item-reactions-count">
                                    <svg class="discussion-item-meta-icon" viewBox="0 -960 960 960" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M720-120H280v-520l280-280 50 50q7 7 11.5 19t4.5 23v14l-44 174h258q32 0 56 24t24 56v80q0 7-2 15t-4 15L794-168q-9 20-30 34t-44 14Zm-360-80h360l120-280v-80H480l54-220-174 174v406Zm0-406v406-406Zm-80-34v80H160v360h120v80H80v-520h200Z"/></svg>
                                    <span>{{ $reactionCount === 0 ? $translator->trans('commently-custom-frontend.discussions.react') : $translator->trans('commently-custom-frontend.discussions.react_with_count', ['count' => $reactionCount]) }}</span>
                                </span>
                            </span>
                            <button type="button" class="discussion-item-menu-btn" aria-label="{{ $translator->trans('commently-custom-frontend.discussions.menu_aria') }}" onclick="event.preventDefault(); event.stopPropagation();">
                                <svg class="discussion-item-menu-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="currentColor">
                                    <circle cx="5" cy="12" r="2"/>
                                    <circle cx="12" cy="12" r="2"/>
                                    <circle cx="19" cy="12" r="2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($page > 1)
            <a href="{{ $listUrl($page - 1) }}" data-turbo="true">{{ $translator->trans('commently-custom-frontend.discussions.previous') }}</a>
        @endif
        @if (!empty($hasNextPage))
            <a href="{{ $listUrl($page + 1) }}" data-turbo="true">{{ $translator->trans('commently-custom-frontend.discussions.next') }}</a>
        @endif
        </div>
    @else
        <p class="discussions-empty-message">
            @if (!($discussionsApiOk ?? true))
                {{ $translator->trans('commently-custom-frontend.discussions.load_error') }}
            @else
                {{ $translator->trans('commently-custom-frontend.discussions.no_discussions') }}
            @endif
        </p>
        @if (!empty($discussionsDebug))
            <p class="discussions-debug" style="margin-top:1rem;font-size:0.875rem;color:#666;">
                Debug (add <code>?debug_discussions=1</code> to URL): API status {{ $discussionsDebug['status'] }}, data count {{ $discussionsDebug['dataCount'] }}, actor {{ $discussionsDebug['actorId'] }}.
            </p>
            @if (!empty($discussionsDebug['errorDetail']))
                <p class="discussions-debug-error" style="margin-top:0.5rem;font-size:0.875rem;color:#c00;white-space:pre-wrap;word-break:break-word;">{{ $discussionsDebug['errorDetail'] }}</p>
            @endif
        @endif
    @endif

    <script>
        (function () {
            var headerWrite = document.getElementById('discussions-header-write');
            var floatingWrite = document.getElementById('discussions-write-floating');
            if (!headerWrite || !floatingWrite) return;
            var mq = window.matchMedia('(max-width: 48rem)');
            var scrollObserver = null;
            function setup() {
                if (scrollObserver) {
                    scrollObserver.disconnect();
                    scrollObserver = null;
                }
                if (mq.matches) {
                    floatingWrite.removeAttribute('hidden');
                    return;
                }
                scrollObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            floatingWrite.setAttribute('hidden', '');
                        } else {
                            floatingWrite.removeAttribute('hidden');
                        }
                    });
                }, { threshold: 0, rootMargin: '0px' });
                scrollObserver.observe(headerWrite);
            }
            setup();
            mq.addEventListener('change', setup);
        })();
    </script>
@endsection
