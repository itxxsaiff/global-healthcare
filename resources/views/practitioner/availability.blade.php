@extends('layouts.dashboard')
@section('title', 'Availability')
@section('crumb', 'Availability')
@php $order = [1, 2, 3, 4, 5, 6, 0]; @endphp
@section('content')
<h1 class="dash-title">Availability</h1>
<p class="text-muted mb-4">Set your usual weekly hours in <strong class="text-ink">{{ str_replace('_', ' ', $profile->timezone) }}</strong> time. Patients anywhere see them converted to their own time zone. Sessions are {{ $profile->session_minutes }} minutes.</p>

<div class="row g-4">
    <div class="col-xl-7">
        <form method="POST" action="{{ route('practitioner.availability.update') }}" class="card-soft p-4"
              x-data="{ days: {{ Js::from(collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => $rules[$d] ?? []])) }} }">
            @csrf @method('PUT')
            <h2 class="h5 mb-3">Weekly hours</h2>
            @foreach ($order as $d)
                <div class="d-flex flex-wrap align-items-start gap-3 py-3 border-bottom">
                    <div style="width: 7.5rem" class="pt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="day-{{ $d }}" :checked="days[{{ $d }}].length > 0"
                                   @change="days[{{ $d }}] = $event.target.checked ? [{ start: '16:00', end: '20:00' }] : []">
                            <label class="form-check-label fw-semibold" for="day-{{ $d }}">{{ \App\Models\AvailabilityRule::DAYS[$d] }}</label>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <template x-if="days[{{ $d }}].length === 0"><div class="text-muted small pt-2">Unavailable</div></template>
                        <template x-for="(w, i) in days[{{ $d }}]" :key="i">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="time" step="900" class="form-control form-control-sm" style="max-width: 8.5rem" :name="'days[{{ $d }}][' + i + '][start]'" x-model="w.start" required>
                                <span class="text-muted">to</span>
                                <input type="time" step="900" class="form-control form-control-sm" style="max-width: 8.5rem" :name="'days[{{ $d }}][' + i + '][end]'" x-model="w.end" required>
                                <button type="button" class="btn btn-sm btn-link text-muted" @click="days[{{ $d }}].splice(i, 1)" aria-label="Remove"><x-icon name="x" /></button>
                            </div>
                        </template>
                        <button type="button" class="btn btn-sm btn-link px-0" x-show="days[{{ $d }}].length > 0 && days[{{ $d }}].length < 4" @click="days[{{ $d }}].push({ start: '18:00', end: '21:00' })"><x-icon name="plus" size="sm" /> Add another window</button>
                    </div>
                </div>
            @endforeach
            <x-field-error name="days" />
            <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary">Save weekly hours</button></div>
        </form>
    </div>

    <div class="col-xl-5">
        <div class="card-soft p-4 mb-4">
            <h2 class="h5 mb-3">Days off & extra hours</h2>
            <form method="POST" action="{{ route('practitioner.availability.exceptions.store') }}" x-data="{ type: 'off' }">
                @csrf
                <div class="segmented mb-3">
                    <input type="radio" name="type" id="t-off" value="off" x-model="type"><label for="t-off">Day off</label>
                    <input type="radio" name="type" id="t-extra" value="extra" x-model="type"><label for="t-extra">Extra hours</label>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-12"><input type="date" name="date" min="{{ now($profile->timezone)->toDateString() }}" class="form-control @error('date') is-invalid @enderror" required><x-field-error name="date" /></div>
                    <div class="col-6" x-show="type === 'extra'" x-cloak><input type="time" step="900" name="start_time" class="form-control" :required="type === 'extra'"></div>
                    <div class="col-6" x-show="type === 'extra'" x-cloak><input type="time" step="900" name="end_time" class="form-control" :required="type === 'extra'"><x-field-error name="end_time" /></div>
                    <div class="col-12"><input name="note" maxlength="120" class="form-control" placeholder="Note (only you see this)"></div>
                </div>
                <button class="btn btn-paper w-100">Add</button>
            </form>
        </div>
        <div class="card-soft p-4">
            <h2 class="h6 mb-2">Coming up</h2>
            @forelse ($exceptions as $e)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                    <div>
                        <strong>{{ $e->date->format('D j M') }}</strong>
                        · {{ $e->is_unavailable ? 'Day off' : 'Extra '.substr($e->start_time, 0, 5).'–'.substr($e->end_time, 0, 5) }}
                        @if ($e->note)<span class="text-muted">· {{ $e->note }}</span>@endif
                    </div>
                    <form method="POST" action="{{ route('practitioner.availability.exceptions.destroy', $e->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-muted p-0" aria-label="Remove"><x-icon name="trash" /></button></form>
                </div>
            @empty
                <p class="small text-muted mb-0">No days off or extra hours scheduled.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
