@extends('custom-frontend::layout')

@section('title', 'Post #' . $id)

@section('content')
    <h1>Post #{{ $id }}</h1>
    <p>Custom post view.</p>
@endsection
