@foreach ($messages as $m)
    @php $staff = $m->user->isAdmin(); @endphp
    <div class="d-flex gap-3 mb-3 {{ $staff ? '' : 'flex-row-reverse text-end' }}">
        <x-avatar :name="$staff ? config('app.name') : $m->user->name" size="sm" round />
        <div class="p-3 rounded-4 {{ $m->is_internal ? 'bg-warning-subtle border border-warning' : ($staff ? 'bg-sage' : 'bg-white border') }}" style="max-width: 80%">
            <div class="small fw-semibold mb-1">{{ $staff ? ($m->is_internal ? 'Internal note · '.$m->user->name : config('app.name').' support') : $m->user->name }} <span class="text-muted fw-normal">· {{ $m->created_at->diffForHumans() }}</span></div>
            <div class="text-start" style="white-space: pre-line">{{ $m->body }}</div>
            @if ($m->attachment_path)<a href="{{ route('tickets.attachment', $m) }}" class="small d-inline-flex gap-1 align-items-center mt-2"><x-icon name="file-text" size="sm" /> {{ $m->attachment_name }}</a>@endif
        </div>
    </div>
@endforeach
