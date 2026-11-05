@extends('errors.layout')

@section('title', 'Server Error (500)')

@section('content')
    <span class="code-pill">500 · Server Error</span>
    <h1 class="headline">A temporary pause in motion.</h1>
    <p class="description">
        We encountered an unexpected issue while processing your request. Our team has been alerted, and service is being restored.
    </p>
    <div class="actions">
        <button onclick="window.location.reload();" class="btn-primary">Try Again</button>
        <a href="/shop" class="btn-secondary">Back to Shop</a>
    </div>
@endsection
