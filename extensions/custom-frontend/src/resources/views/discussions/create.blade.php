@extends('custom-frontend::layout')

@section('title', $translator->trans('commently-custom-frontend.create.title'))

@section('content')
    <h1>{{ $translator->trans('commently-custom-frontend.create.title') }}</h1>

    <form method="post" action="{{ $url->to('forum')->route('custom-frontend.discussions.create') }}" data-turbo="false" onsubmit="var btn = this.querySelector('button[type=submit]'); if (btn && !btn.disabled) { btn.disabled = true; var t = btn.getAttribute('data-submitting'); if (t) btn.textContent = t; }">
        <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
        <p>
            <label for="title">{{ $translator->trans('commently-custom-frontend.create.title_label') }} <em>{{ $translator->trans('commently-custom-frontend.create.title_optional') }}</em></label><br>
            <input type="text" name="title" id="title" maxlength="80" placeholder="{{ $translator->trans('commently-custom-frontend.create.title_placeholder') }}">
        </p>
        @if (count($tagsForSelect ?? []) > 0)
            <p class="discussion-create-tags">
                <span class="discussion-create-tags-label">{{ $translator->trans('commently-custom-frontend.create.tags_label') }}</span><br>
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
            <label for="content">{{ $translator->trans('commently-custom-frontend.create.content_label') }} <strong>{{ $translator->trans('commently-custom-frontend.create.content_required') }}</strong></label><br>
            <textarea name="content" id="content" rows="8" required placeholder="{{ $translator->trans('commently-custom-frontend.create.content_placeholder') }}"></textarea>
        </p>
        <p>
            <button type="submit" data-submitting="{{ $translator->trans('commently-custom-frontend.create.submitting') }}">{{ $translator->trans('commently-custom-frontend.create.submit') }}</button>
        </p>
    </form>

    <p><a href="{{ $url->to('forum')->route('custom-frontend.index') }}" data-turbo="true">{{ $translator->trans('commently-custom-frontend.create.back') }}</a></p>
@endsection
