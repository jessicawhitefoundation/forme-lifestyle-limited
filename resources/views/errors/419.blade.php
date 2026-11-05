@extends('errors.layout')

@section('title', 'Session Expired (419)')

@section('content')
    <span class="code-pill">419 · Session Expired</span>
    <h1 class="headline">Your session took a breather.</h1>
    <p class="description">
        For your security, page sessions expire after a period of inactivity. Please refresh the page to continue.
    </p>
    <div class="actions">
        <button onclick="window.location.reload();" class="btn-primary">Refresh &amp; Continue</button>
        <a href="/" class="btn-secondary">Return Home</a>
    </div>
@endsection
