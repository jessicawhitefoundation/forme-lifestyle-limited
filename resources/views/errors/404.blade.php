@extends('errors.layout')

@section('title', 'Page Not Found (404)')

@section('content')
    <span class="code-pill">404 · Not Found</span>
    <h1 class="headline">This piece seems out of reach.</h1>
    <p class="description">
        The item, collection, or page you are looking for may have moved, been renamed, or is currently unavailable.
    </p>
    <div class="actions">
        <a href="/shop" class="btn-primary">Explore Collection</a>
        <a href="/" class="btn-secondary">Return Home</a>
    </div>
@endsection
