@extends('errors.layout')

@section('title', 'Under Maintenance (503)')

@section('content')
    <span class="code-pill">503 · Scheduled Maintenance</span>
    <h1 class="headline">Enhancing your active experience.</h1>
    <p class="description">
        We are currently making brief enhancements to our store. We will be back online shortly. Thank you for your patience!
    </p>
    <div class="actions">
        <button onclick="window.location.reload();" class="btn-primary">Check Again</button>
        <a href="https://wa.me/{{ config('store.whatsapp_number') }}" target="_blank" class="btn-secondary">Message on WhatsApp</a>
    </div>
@endsection
