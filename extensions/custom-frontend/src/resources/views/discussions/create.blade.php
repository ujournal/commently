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
        @if (count($tagsForSelect ?? []) > 0)
            <p class="discussion-create-tags">
                <span class="discussion-create-tags-label">Tags</span><br>
                <span class="discussion-create-tags-list">
                    @foreach ($tagsForSelect as $tag)
                        @php
                            $tagId = $tag['id'] ?? '';
                            $name = $tag['attributes']['name'] ?? $tagId;
                            $isChild = !empty($tag['attributes']['isChild'] ?? false);
                        @endphp
                        <label class="discussion-create-tag-item {{ $isChild ? 'discussion-create-tag-child' : '' }}">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tagId }}">
                            <span class="discussion-create-tag-name">{{ $name }}</span>
                        </label>
                    @endforeach
                </span>
            </p>
        @endif
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
