@props([
    'complaint',
    'staff' => false,
])

{{-- The record's facts in one place. Staff also need who reported the ticket
     and when it closed; the student already knows both. The status chip lives
     once, in the card header — repeating it here restated what the screen
     already said. `staff` is passed rather than inferred: the template should
     not know which role is reading it. --}}
<dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
    @if ($staff)
        <div>
            <dt class="font-medium text-on-surface-variant">Pelapor</dt>
            <dd class="mt-0.5 font-medium text-on-surface">{{ $complaint->user->name }}</dd>
        </div>
        <div>
            <dt class="font-medium text-on-surface-variant">Email</dt>
            <dd class="mt-0.5 text-on-surface">{{ $complaint->user->email }}</dd>
        </div>
    @endif

    <div>
        <dt class="font-medium text-on-surface-variant">Kategori</dt>
        <dd class="mt-0.5 text-on-surface"><span class="chip bg-secondary-container text-on-secondary-container">{{ $complaint->category->name }}</span></dd>
    </div>
    <div>
        <dt class="font-medium text-on-surface-variant">Ruang</dt>
        <dd class="mt-0.5 text-on-surface">
            {{ $complaint->room->name }}
            <span class="text-on-surface-variant">({{ $complaint->room->code }})</span>
        </dd>
    </div>
    <div>
        <dt class="font-medium text-on-surface-variant">Diajukan pada</dt>
        <dd class="mt-0.5 text-on-surface">{{ $complaint->created_at->translatedFormat('d F Y, H:i') }}</dd>
    </div>

    {{-- The status update stamps `resolved_at`, but a factory, import, or
         direct write can leave it unset. Only claim a completion time the
         record actually has. --}}
    @if ($staff && $complaint->status === 'resolved' && $complaint->resolved_at)
        <div>
            <dt class="font-medium text-on-surface-variant">Selesai pada</dt>
            <dd class="mt-0.5 text-on-surface">{{ $complaint->resolved_at->translatedFormat('d F Y, H:i') }}</dd>
        </div>
    @endif
</dl>
