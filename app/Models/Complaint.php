<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#[Fillable([
    'ticket_number',
    'user_id',
    'category_id',
    'room_id',
    'title',
    'description',
    'evidence_image',
    'status',
    'admin_notes',
    'resolved_image',
    'resolved_at',
])]
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    /**
     * The statuses a complaint can move through, in display order.
     *
     * @var list<string>
     */
    public const STATUSES = ['pending', 'in_progress', 'resolved', 'rejected'];

    /**
     * The Indonesian label for each status — the single source for the status
     * badge, the filter chips, the dashboard cards, the status select and the
     * printed recap, so no one of them can rename a status on its own.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pending' => 'Menunggu',
        'in_progress' => 'Dalam Proses',
        'resolved' => 'Selesai',
        'rejected' => 'Ditolak',
    ];

    /**
     * The transition contract each TARGET status carries, shared by every
     * surface that changes a complaint's status (admin detail, teknisi
     * detail, future bulk flows). `proof` says the transition carries the
     * proof-image field (stored when a file arrives — a requirement is the
     * request layer's job, not the table's); `notes` says its note is
     * required (rejected's justification); `final` says the state ends the
     * ticket — a different target from a final state is refused.
     *
     * @var array<string, array{proof: bool, notes: bool, final: bool}>
     */
    public const TRANSITIONS = [
        'pending' => ['proof' => false, 'notes' => false, 'final' => false],
        'in_progress' => ['proof' => false, 'notes' => false, 'final' => false],
        'resolved' => ['proof' => true, 'notes' => false, 'final' => true],
        'rejected' => ['proof' => false, 'notes' => true, 'final' => true],
    ];

    protected static function booted(): void
    {
        static::creating(function (Complaint $complaint) {
            if (empty($complaint->ticket_number)) {
                $complaint->ticket_number = static::generateTicketNumber();
            }
        });
    }

    /**
     * The label for a status, falling back to the raw value so a status we have no
     * label for still reads (which is why the status badge's guard is blank-only).
     */
    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Move the complaint to another status through the shared transition
     * contract (TRANSITIONS), single-sourcing every surface's checks, the
     * resolved_at stamp, and proof-image storage. Throws loudly — the caller
     * (a request layer has usually already validated intent) surfaces the
     * contract violation to the user, never silently half-applies it.
     *
     * @param  array{admin_notes?: ?string, proof?: ?string}  $data  the note as
     *                                                               given (written verbatim, matching the one-form-overwrites flow) and
     *                                                               the already-stored proof path, when one arrived
     *
     * @throws \LogicException on an unknown status, a flip out of a final
     *                         state, or a resolved arrival without the contract's proof
     */
    public function applyTransition(string $status, array $data = []): void
    {
        if (! in_array($status, static::STATUSES, true)) {
            throw new \LogicException('Status pengaduan tidak dikenal.');
        }

        // A final state ends the ticket; the only thing it accepts is a
        // re-save of its own state (a note typo, re-attaching proof).
        if ($this->status !== $status
            && (static::TRANSITIONS[$this->status]['final'] ?? false)) {
            throw new \LogicException('Pengaduan sudah berakhir; statusnya tidak dapat diubah lagi.');
        }

        $fill = ['status' => $status];

        if (array_key_exists('admin_notes', $data)) {
            $fill['admin_notes'] = $data['admin_notes'];
        }

        if ($status === 'resolved') {
            // The stamp happens once: on the transition INTO Selesai, not on
            // a proof re-attach of an already resolved complaint.
            if ($this->status !== 'resolved') {
                $fill['resolved_at'] = now();
            }
        }

        if ($this::TRANSITIONS[$status]['proof'] && ! empty($data['proof'])) {
            $fill['resolved_image'] = $data['proof'];
        }

        $this->fill($fill)->save();
    }

    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT-'.now()->format('Ymd').'-';
        $last = static::query()->where('ticket_number', 'like', $prefix.'%')->latest('id')->value('ticket_number');
        $next = Str::afterLast((string) $last, '-') !== '' ? ((int) Str::afterLast((string) $last, '-')) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Complaint totals keyed by status, plus a `total` key.
     *
     * Without a search the counts are global — every list view, chip, and
     * dashboard card must read the same numbers so they can never disagree
     * when no filter is active; with a period, callers count the same
     * window as the rows. With a search, the counts live inside that same
     * search the rows use: a chip's number must always equal what clicking
     * it reveals, and a list can never show "Menunggu 12" while every chip
     * click with the current query lands on 0 rows. (Dashboard cards call
     * this bare, so they always stay all-time.) A category works the same
     * way — the counts sit inside the active dimension so the chips cannot
     * propose numbers the click will contradict. Missing statuses are
     * zero-filled so callers never need null checks.
     *
     * @return array{total: int, pending: int, in_progress: int, resolved: int, rejected: int}
     */
    public static function statusCounts(?array $period = null, ?string $search = null, ?int $categoryId = null): array
    {
        $byStatus = static::query()
            ->period($period)
            ->search($search)
            ->category($categoryId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['total' => 0];

        foreach (static::STATUSES as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
            $counts['total'] += $counts[$status];
        }

        return $counts;
    }

    /**
     * Interpret the request's ?from/?to as a filing-date period, or none.
     *
     * The bounds are UTC instants of Jakarta day-edges: the model stores
     * UTC timestamps while WIB is the calendar the school thinks in. A
     * reversed pair normalizes; a half-pair or junk disables the period —
     * a hand-edited URL is a reading mistake, never an error page.
     *
     * @return array{from: string, to: string, label: string, bounds: array{0: CarbonImmutable, 1: CarbonImmutable}}|null
     */
    public static function resolvePeriod(?Request $request = null): ?array
    {
        $request ??= request();

        $from = static::parseYmd((string) $request->query('from'));
        $to = static::parseYmd((string) $request->query('to'));

        if ($from === null || $to === null) {
            return null;
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'label' => static::periodLabel($from, $to),
            'bounds' => [
                $from->startOfDay()->utc(),
                $to->endOfDay()->utc(),
            ],
        ];
    }

    /**
     * Interpret the request's ?category as an existing category id, or none.
     *
     * The filter-grammar companion of resolvePeriod: a missing, non-numeric,
     * or nonexistent id (a stale bookmark, a hand-edited URL) resolves to
     * null and narrows nothing — the list reads on the remaining filters,
     * never a 500 and never a category the picker would not display.
     */
    public static function resolveCategory(?Request $request = null): ?int
    {
        $raw = ($request ?? request())->query('category');

        if (! is_numeric($raw)) {
            return null;
        }

        return Category::query()->whereKey((int) $raw)->exists() ? (int) $raw : null;
    }

    /**
     * Parse a strict Y-m-d calendar date in Jakarta wall-time, or null.
     *
     * The round-trip check catches values Carbon would silently roll over
     * (month 99 becomes November 9), which are junk the caller never typed.
     */
    private static function parseYmd(string $raw): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $raw, new \DateTimeZone('Asia/Jakarta'));

        if ($date === false || $date->toDateString() !== $raw) {
            return null;
        }

        return $date;
    }

    /**
     * The Indonesian human phrase for a period, e.g. 1–15 Oktober 2026.
     *
     * Non-padded day numbers (j F Y) match the house style used for
     * translatedFormat('d M Y') timestamps across the lists.
     */
    private static function periodLabel(CarbonImmutable $from, CarbonImmutable $to): string
    {
        if ($from->toDateString() === $to->toDateString()) {
            return $from->translatedFormat('j F Y');
        }

        if ($from->isSameMonth($to)) {
            return $from->translatedFormat('j').'–'.$to->translatedFormat('j F Y');
        }

        if ($from->isSameYear($to)) {
            return $from->translatedFormat('j F').' – '.$to->translatedFormat('j F Y');
        }

        return $from->translatedFormat('j F Y').' – '.$to->translatedFormat('j F Y');
    }

    /**
     * The four preset periods, materialized to explicit dates.
     *
     * Each chip renders these as href bounds, so a saved URL keeps printing
     * the same window even when it is reopened next month. Reads Jakarta
     * wall-time "now" at render time — a preset is an affordance, not state.
     *
     * @return array<int, array{name: string, from: string, to: string}>
     */
    public static function presetPeriods(): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');

        return [
            ['name' => 'Hari ini', 'from' => $now->toDateString(), 'to' => $now->toDateString()],
            ['name' => '7 hari', 'from' => $now->subDays(6)->toDateString(), 'to' => $now->toDateString()],
            ['name' => 'Bulan ini', 'from' => $now->startOfMonth()->toDateString(), 'to' => $now->endOfMonth()->toDateString()],
            ['name' => 'Tahun ini', 'from' => $now->startOfYear()->toDateString(), 'to' => $now->endOfYear()->toDateString()],
        ];
    }

    /**
     * The free-text search the list and its chips share.
     *
     * Matches every field the row displays that an admin triages by: title,
     * ticket number, description — and the reporter and room names, whose
     * columns the table shows. A blank or missing term applies nothing, so
     * a caller can pass `request('q')` directly.
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        if (trim((string) $search) === '') {
            return;
        }

        $term = "%{$search}%";

        $query->where(function (Builder $query) use ($term) {
            $query->where('title', 'like', $term)
                ->orWhere('ticket_number', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $term))
                ->orWhereHas('room', fn (Builder $room) => $room->where('name', 'like', $term));
        });
    }

    /**
     * The damage-category dimension shared by the list, its chips, and the
     * report: an active id narrows to it, null applies nothing.
     */
    public function scopeCategory(Builder $query, ?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        $query->where('category_id', $categoryId);
    }

    /**
     * Apply the period bounds created at BETWEEN, or nothing for null.
     *
     * whereBetween (rather than two whereDate calls) keeps any index on
     * created_at usable; at school scale it is not measurable either way.
     */
    public function scopePeriod(Builder $query, ?array $period): void
    {
        if ($period === null) {
            return;
        }

        $query->whereBetween('created_at', $period['bounds']);
    }

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
