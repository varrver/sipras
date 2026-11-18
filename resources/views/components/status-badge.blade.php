@props(['status'])

@php
    // A blank status renders a chip with *no text*: a neutral gray pill that names
    // nothing, and no axe rule catches an empty one. (An unknown but present
    // status still falls back to its raw label below — this guard is blank-only,
    // not a whitelist, so a new status is never a hard failure.) `@props` leaves an
    // omitted prop undefined, so that path would otherwise be a bare "Undefined
    // variable $status".
    if (trim((string) ($status ?? '')) === '') {
        throw new LogicException(
            'The route ['.(request()->route()?->getName() ?? 'unnamed route').'] rendered '.
            'x-status-badge without a status. An empty chip names nothing — see DESIGN.md §3.'
        );
    }

    $styles = [
        'pending' => 'bg-warning-container text-on-warning-container',
        'in_progress' => 'bg-tertiary-container text-on-tertiary-container',
        'resolved' => 'bg-success-container text-on-success-container',
        'rejected' => 'bg-error-container text-on-error-container',
    ];
    // The label comes from the model, not a local copy: the chips, the dashboard
    // cards, the status select and the printed recap all read `STATUS_LABELS`, so
    // they cannot rename a status on their own. Only the *tints* live here — they
    // are design tokens (DESIGN.md §3), not vocabulary.
@endphp

<span class="chip whitespace-nowrap {{ $styles[$status] ?? 'bg-secondary-container text-on-secondary-container' }}">
    {{ \App\Models\Complaint::statusLabel($status) }}
</span>