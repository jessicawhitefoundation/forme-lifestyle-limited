@extends('errors.layout')

@section('title', 'Access Restricted (403)')

@section('content')
    <span class="code-pill">403 · Access Restricted</span>
    <h1 class="headline">Restricted Area.</h1>
    <p class="description">
        You do not have permission to access this page. Please sign in with an authorized account or return to the main store.
    </p>
    <div class="actions">
        <a href="/login" class="btn-primary">Sign In</a>
        <a href="/" class="btn-secondary">Return Home</a>
    </div>
@endsection
