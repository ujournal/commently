@extends('custom-frontend::layout')

@section('title', 'Discussions')

@section('content')
    <h1>Discussions</h1>

    <p><a href="{{ $url->to('forum')->route('custom-frontend.discussions.create.page') }}" data-turbo="true">Start a discussion</a></p>

    @if (isset($apiDocument->data) && count($apiDocument->data) > 0)
        <ul class="discussion-list">
            @foreach ($apiDocument->data as $discussion)
                <li class="discussion-item">
                    <a href="{{ $url->to('forum')->route('custom-frontend.discussion', ['id' => $discussion->id]) }}" data-turbo="true">
                        {{ $discussion->attributes->title ?? 'Discussion #' . $discussion->id }}
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
