@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="notice notice--danger">{{ session('error') }}</div>
    @endif

    @include('partials.skripsi.detail-template')
@endsection
