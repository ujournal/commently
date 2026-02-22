@extends('custom-frontend::layout')

@section('title', $translator->trans('commently-custom-frontend.post.title', ['id' => $id]))

@section('content')
    <h1>{{ $translator->trans('commently-custom-frontend.post.title', ['id' => $id]) }}</h1>
    <p>{{ $translator->trans('commently-custom-frontend.post.custom_view') }}</p>
@endsection
