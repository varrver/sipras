<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teknisi\TeknisiComplaintStatusRequest;
use App\Models\Category;
use App\Models\Complaint;
use Illuminate\Contracts\View\View;

/**
 * The technician's guarded complaint surface: the queue (the same list
 * grammar the admin list speaks — newest-first, status chips, search,
 * period — scoped to this technician's specialty, with none of the
 * admin-specific capabilities) and the detail page where the three
 * transitions live (start work, finish with proof, reject with
 * justification), all routed through the model's shared transition contract.
 */
class TeknisiComplaintController extends Controller
{
    /**
     * The worklist. The same scopes and toolbar grammar as the admin list
     * (role-blind scopes, shared with the rows) plus the technician's own
     * specialty as the implicit first filter — the queue is one category, so
     * the category picker is dropped rather than made redundant.
     */
    public function index(): View
    {
        $period = Complaint::resolvePeriod();
        $search = request('q');
        $specialtyId = auth()->user()->category_id;

        $complaints = Complaint::query()
            ->where('category_id', $specialtyId)
            ->period($period)
            ->search($search)
            ->with(['user', 'category', 'room'])
            // Mirror the student list: an unrecognised status filters
            // nothing, because it is a hand-edited URL rather than a value
            // the chips can produce.
            ->when(in_array(request('status'), Complaint::STATUSES, true), fn ($query) => $query->where('status', request('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('teknisi.pengaduan.index', [
            'complaints' => $complaints,
            'period' => $period,
            'specialty' => Category::find($specialtyId),
        ]);
    }

    /**
     * The detail: full record; the status form appears only while the
     * complaint is still non-final. A complaint outside the technician's
     * specialty refuses with the localized error card (D4) — the queue and
     * the extension of read+mutate authority stop at that boundary.
     */
    public function show(Complaint $complaint): View
    {
        if ($complaint->category_id !== auth()->user()->category_id) {
            abort(403, 'Pengaduan ini di luar spesialisasi Anda.');
        }

        $complaint->load(['user', 'category', 'room']);

        return view('teknisi.pengaduan.show', compact('complaint'));
    }

    /**
     * One of the three transitions, validated (proof or justification as
     * contracted, specialty as the request's closure rule) and anchored to
     * the model's shared helper. Lands on the feedback same as the admin
     * flow does.
     */
    public function updateStatus(TeknisiComplaintStatusRequest $request, Complaint $complaint)
    {
        $complaint->applyTransition(
            $request->status,
            [
                'admin_notes' => $request->safe()->only(['admin_notes'])['admin_notes'] ?? null,
                'proof' => $request->hasFile('resolved_image')
                    ? $request->file('resolved_image')->store('complaints/resolved', 'public')
                    : null,
            ]
        );

        session()->flash('success', 'Status pengaduan berhasil diperbarui.');

        return redirect()->to(route('teknisi.pengaduan.show', $complaint).'#flash-success');
    }
}
