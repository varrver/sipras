@props(['status', 'createdAt', 'resolvedAt'])

@php
    // The timeline shows the complaint's history and an honest end. Labels come
    // from STATUS_LABELS — the same map as the badge, chips and printed recap —
    // timestamps from the columns the schema actually has (created_at,
    // resolved_at), so nothing is invented.
    //
    // Two rules keep the walk truthful:
    //  1. A stage renders only if the complaint plausibly reached it. Rejected
    //     is terminal, not a stage: it was filed, refused and returned to the
    //     student to refile — so it is kept out of the forward walk entirely
    //     (a living complaint only shows stages it can still reach), and a
    //     rejected complaint gets the created stage plus the explicit red
    //     end-node, nothing in between.
    //  2. An end-state gets its own node state, never one styled as waiting:
    //     resolved closes with a filled success check (terminal-complete),
    //     rejected with an error cross (terminal-rejected). A finished track
    //     must not say "sedang berlangsung" about its own last node.
    $isRejected = $status === 'rejected';

    $walked = $isRejected
        ? collect(['pending', 'rejected'])
        : collect(\App\Models\Complaint::STATUSES)->reject(fn (string $s) => $s === 'rejected');

    $steps = $walked->map(fn (string $s): array => [
        'status' => $s,
        'label' => \App\Models\Complaint::STATUS_LABELS[$s],
        'at' => match ($s) {
            'pending' => $createdAt,
            'resolved' => $resolvedAt,
            default => null,
        },
    ]);

    $currentIndex = (int) array_search($status, \App\Models\Complaint::STATUSES, true);

    // Node states, mirrored by the data-state attribute and the shapes below:
    // done (filled check), active (open ring + suffix), todo (empty circle),
    // terminal-complete / terminal-rejected (the track's end).
    $state = fn (string $s, int $index): string => match (true) {
        $s === 'rejected' => 'terminal-rejected',
        $s === 'resolved' && $index === $currentIndex => 'terminal-complete',
        $index < $currentIndex => 'done',
        $index === $currentIndex => 'active',
        default => 'todo',
    };

    // ~4.4:1 on the white card — same AA margin as the hover wash (DESIGN.md).
    $trackDone = 'bg-primary/40';
@endphp

<ol {{ $attributes }}>
    @foreach ($steps as $step)
        @php $s = $state($step['status'], $loop->index); @endphp
        <li data-state="{{ $s }}" class="relative flex gap-3.5 pb-6 last:pb-0">
            @unless ($loop->last)
                {{-- The track is decorative reinforcement; the state itself is
                     carried by the node shape, ink and data-state attribute. --}}
                <span aria-hidden="true"
                    class="absolute left-[11px] top-7 bottom-0 w-0.5 {{ $s === 'done' ? $trackDone : 'bg-outline-variant' }}"></span>
            @endunless

            @if ($s === 'done')
                <span aria-hidden="true"
                    class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary">
                    <svg class="h-3.5 w-3.5 text-on-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
                <span class="sr-only">Sudah dilalui</span>
            @elseif ($s === 'terminal-complete')
                {{-- The node shape is the state: a caught-up check, filled in the
                     success ink of the Selesai chip. No sr-only state word — the
                     label already reads "Selesai". --}}
                <span aria-hidden="true"
                    class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-success">
                    <svg class="h-3.5 w-3.5 text-on-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
            @elseif ($s === 'terminal-rejected')
                {{-- Same geometry as the completion check, in the error ink of
                     the Ditolak chip: the verdict, not a stage. No sr-only word
                     — the label already reads "Ditolak". --}}
                <span aria-hidden="true"
                    class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-error">
                    <svg class="h-3.5 w-3.5 text-on-error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </span>
            @elseif ($s === 'active')
                <span aria-hidden="true"
                    class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-surface-container ring-[3px] ring-primary">
                    <span class="h-2 w-2 rounded-full bg-primary"></span>
                </span>
                <span class="sr-only">Sedang berlangsung</span>
            @else
                <span aria-hidden="true" class="relative z-10 h-6 w-6 shrink-0 rounded-full border-2 border-outline bg-surface-container"></span>
            @endif

            <div class="min-w-0 pt-0.5">
                <p class="text-sm font-semibold {{ $s === 'todo' ? 'text-on-surface-variant' : 'text-on-surface' }}">
                    {{ $step['label'] }}@if ($s === 'active') — sedang berlangsung @endif
                </p>
                @if ($at = $step['at'])
                    <p class="mt-0.5 text-xs text-on-surface-variant">{{ $at->translatedFormat('d M Y, H:i') }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
