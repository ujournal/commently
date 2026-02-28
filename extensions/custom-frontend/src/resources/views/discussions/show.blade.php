@extends('custom-frontend::layout')

@section('title', $apiDocument->data->attributes->title ?? $translator->trans('commently-custom-frontend.show.default_title'))
@section('bodyClass', 'page-discussion')

@section('content')
    <div class="discussion-page">
        <div class="discussion-header">
            <h1>{{ $apiDocument->data->attributes->title ?? $translator->trans('commently-custom-frontend.show.default_title') }}</h1>
        </div>

        <div class="Discussion-posts">
            @foreach ($posts as $post)
                @php
                    $user = isset($post->relationships->user->data) ? $getResource($post->relationships->user->data) : null;
                    $isFirstPost = $post->attributes->number === 1;
                    $tagLinks = $isFirstPost && isset($apiDocument->data->relationships->tags->data) ? (array) $apiDocument->data->relationships->tags->data : [];
                    $tags = array_filter(array_map(fn ($link) => $getResource($link), $tagLinks));
                @endphp
                <article class="Post {{ $isFirstPost ? 'Post-first' : '' }}">
                    @if ($isFirstPost)
                        <div class="discussion-item-header Post-first-header">
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
                                @if (!empty($post->attributes->createdAt ?? null))
                                    @php
                                        $createdAt = \Carbon\Carbon::parse($post->attributes->createdAt);
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
                    @else
                        <div class="Post-user">
                            <strong>{{ $user ? ($user->attributes->displayName ?? $user->attributes->username ?? '') : $translator->trans('core.lib.username.deleted_text') }}</strong>
                        </div>
                    @endif
                    <div class="Post-content content">
                        {!! $post->attributes->contentHtml !!}
                    </div>
                </article>
                <hr>

                @if ($post->attributes->number === 1)
                <h2>Відповіді</h2>
                @endif
            @endforeach
        </div>

        @if ($hasPrevPage)
            <a href="{{ $url(['page' => $page - 1]) }}" data-turbo="true">{{ $translator->trans('commently-custom-frontend.show.previous') }}</a>
        @endif

        @if ($hasNextPage)
            <a href="{{ $url(['page' => $page + 1]) }}" data-turbo="true">{{ $translator->trans('commently-custom-frontend.show.next') }}</a>
        @endif

        <h2>{{ $translator->trans('commently-custom-frontend.show.reply_heading') }}</h2>

        <form method="post" action="{{ $replyUrl }}" data-turbo="false" class="Discussion-replyForm">
            <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
            <p>
                <label for="reply-content">{{ $translator->trans('commently-custom-frontend.show.reply_label') }}</label><br>
                <textarea name="content" id="reply-content" rows="6" required placeholder="{{ $translator->trans('commently-custom-frontend.show.reply_placeholder') }}"></textarea>
            </p>
            <p>
                <button type="submit">{{ $translator->trans('commently-custom-frontend.show.post_reply') }}</button>
            </p>
        </form>
    </div>
@endsection
