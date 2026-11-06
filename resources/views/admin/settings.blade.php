@extends('layouts.dashboard')
@section('title', 'Settings')
@section('crumb', 'Settings')
@section('content')
<h1 class="dash-title">Settings</h1>
<p class="text-muted mb-4">Business rules. Defaults are documented in <code>docs/DECISIONS.md</code>.</p>
<form method="POST" action="{{ route('admin.settings.update') }}" class="card-soft p-4" style="max-width: 760px">
    @csrf @method('PUT')
    @foreach ($fields as $key => [$label, $help])
        <div class="row g-2 align-items-center py-2 border-bottom">
            <div class="col-md-6"><label class="form-label mb-0" for="{{ $key }}">{{ $label }}</label><div class="form-hint mt-0">{{ $help }}</div></div>
            <div class="col-md-6">
                <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, \App\Models\Setting::get($key)) }}" class="form-control @error($key) is-invalid @enderror">
                <x-field-error :name="$key" />
            </div>
        </div>
    @endforeach
    <button class="btn btn-primary mt-3">Save settings</button>
</form>
@endsection
