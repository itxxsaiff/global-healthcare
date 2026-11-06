@extends('layouts.dashboard')
@section('title', 'Specialties & symptoms')
@section('crumb', 'Specialties & symptoms')
@section('content')
<h1 class="dash-title">Specialties & symptoms</h1>
<p class="text-muted mb-4">The 14 main specialties come from the client brief and are fixed. Sub-specialties and symptoms can be added or hidden.</p>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="accordion" id="spec">
            @foreach ($specialties as $s)
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s{{ $s->id }}">{{ $s->name }} <span class="small text-muted ms-2">{{ $s->children->count() }} sub</span></button></h2>
                    <div id="s{{ $s->id }}" class="accordion-collapse collapse" data-bs-parent="#spec">
                        <div class="accordion-body">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach ($s->children as $c)
                                    <form method="POST" action="{{ route('admin.taxonomy.subs.toggle', $c) }}">@csrf<button class="chip {{ $c->is_active ? '' : 'opacity-50 text-decoration-line-through' }}" title="Click to {{ $c->is_active ? 'hide' : 'show' }}">{{ $c->name }}</button></form>
                                @endforeach
                            </div>
                            <form method="POST" action="{{ route('admin.taxonomy.subs.store', $s) }}" class="d-flex gap-2">@csrf<input name="name" class="form-control form-control-sm" placeholder="New sub-specialty" required><button class="btn btn-sm btn-paper">Add</button></form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="col-xl-5">
        <form method="POST" action="{{ route('admin.taxonomy.conditions.store') }}" class="card-soft p-4 mb-4">
            @csrf
            <h2 class="h6">Add a symptom / condition</h2>
            <input name="name" class="form-control mb-2" placeholder="e.g. Back acne" required>
            <select name="specialties[]" multiple size="6" class="form-select mb-2" required>
                @foreach ($specialties as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="feat"><label class="form-check-label small" for="feat">Feature on home page</label></div>
            <button class="btn btn-sm btn-primary">Add symptom</button>
            <x-field-error name="name" />
        </form>
        <div class="card-soft p-4">
            <h2 class="h6">Symptoms ({{ $conditions->count() }})</h2>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($conditions as $c)
                    <form method="POST" action="{{ route('admin.taxonomy.conditions.toggle', $c) }}">@csrf<button class="chip {{ $c->is_active ? '' : 'opacity-50 text-decoration-line-through' }}" title="{{ $c->specialties->pluck('name')->implode(', ') }}">{{ $c->name }}</button></form>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
