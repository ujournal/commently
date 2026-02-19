@extends('custom-frontend::layout')

@section('title', 'New Discussion')

@section('content')
    <h1>New Discussion</h1>

    <form method="post" action="{{ $url->to('forum')->route('custom-frontend.discussions.create') }}" data-turbo="false">
        <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
        <p>
            <label for="title">Title <em>(optional)</em></label><br>
            <input type="text" name="title" id="title" maxlength="80" placeholder="Leave empty for “Posted by @username at date”">
        </p>
        <p>
            <label for="content">Content <strong>(required)</strong></label><br>
            <textarea name="content" id="content" rows="8" required placeholder="Write your post…"></textarea>
        </p>
        <p>
            <button type="submit">Post Discussion</button>
        </p>
    </form>

    <p><a href="{{ $url->to('forum')->route('custom-frontend.index') }}" data-turbo="true">&laquo; Back to discussions</a></p>
@endsection
