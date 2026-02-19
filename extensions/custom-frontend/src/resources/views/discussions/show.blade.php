@extends('custom-frontend::layout')

@section('title', $apiDocument->data->attributes->title ?? 'Discussion')

@section('content')
    <h1>{{ $apiDocument->data->attributes->title ?? 'Discussion' }}</h1>

    <div class="Discussion-posts">
        @foreach ($posts as $post)
            <article class="Post">
                @php
                    $user = isset($post->relationships->user->data) ? $getResource($post->relationships->user->data) : null;
                @endphp
                <div class="Post-user">
                    <strong>{{ $user ? ($user->attributes->displayName ?? $user->attributes->username ?? '') : $translator->trans('core.lib.username.deleted_text') }}</strong>
                </div>
                <div class="Post-content">
                    {!! $post->attributes->contentHtml !!}
                </div>
            </article>
            <hr>
        @endforeach
    </div>

    @if ($hasPrevPage)
        <a href="{{ $url(['page' => $page - 1]) }}" data-turbo="true">&laquo; Previous</a>
    @endif
    @if ($hasNextPage)
        <a href="{{ $url(['page' => $page + 1]) }}" data-turbo="true">Next &raquo;</a>
    @endif

    <h2>Reply</h2>
    <form method="post" action="{{ $replyUrl }}" data-turbo="false" class="Discussion-replyForm">
        <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
        <p>
            <label for="reply-content">Your reply</label><br>
            <textarea name="content" id="reply-content" rows="6" required placeholder="Write your reply…"></textarea>
        </p>
        <p>
            <button type="submit">Post Reply</button>
        </p>
    </form>
@endsection
