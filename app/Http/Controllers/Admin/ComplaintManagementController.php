<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\UpdateComplaintStatusRequest;
use App\Models\Category;
use App\Models\Complaint;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintManagementController extends Controller
{
    /**
     * Display a listing of all complaints.
     */
    public function index()
    {
        // One resolution for rows AND chips: a chip's number must always equal
        // what clicking it reveals. Null means no period — nothing changes; a
        // blank search means no search — nothing changes; an unresolvable
        // category (missing/stale/non-numeric) means no category — nothing
        // changes. Each tier widens the same scope, so "Menunggu 12" can
        // never sit above a query whose click reveals 0. Dashboard cards
        // call statusCounts() bare and keep their all-time numbers — this
        // list's chips are the only readers that inherit the filters.
        $period = Complaint::resolvePeriod();
        $search = request('q');
        $category = Complaint::resolveCategory();

        $query = Complaint::query()
            ->period($period)
            ->search($search)
            ->category($category)
            ->with(['user', 'category', 'room'])
            // Only a known status narrows the list. A junk value is a
            // hand-edited URL, not a filter the UI can produce, so it reads
            // as no filter rather than silently emptying the page — the
            // student list's behaviour.
            ->when(in_array(request('status'), Complaint::STATUSES, true), function ($query) {
                $query->where('status', request('status'));
            });

        $query->latest();

        $complaints = $query->paginate(10)->withQueryString();

        return view('admin.pengaduan.index', [
            'complaints' => $complaints,
            'counts' => Complaint::statusCounts($period, $search, $category),
            'period' => $period,
            'category' => $category,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Display the specified complaint.
     */
    public function show(Complaint $complaint)
    {
        $complaint->load(['user', 'category', 'room']);

        return view('admin.pengaduan.show', compact('complaint'));
    }

    /**
     * Update the complaint status — through the shared transition contract on
     * the model (same helper the teknisi surface calls), so the stamp, proof
     * storage, and final-state rules stay single-sourced.
     */
    public function updateStatus(UpdateComplaintStatusRequest $request, Complaint $complaint)
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

        // Land on the feedback, not the page top: the fragment scrolls the
        // confirmation banner and the record card into view after the save.
        return redirect()->to(route('admin.pengaduan.show', $complaint).'#flash-success');
    }

    /**
     * Display the printable complaint report.
     */
    public function report()
    {
        [$complaints, $summary, $period, $category] = $this->reportData();

        return view('admin.reports.complaints', [
            'complaints' => $complaints,
            'summary' => $summary,
            'period' => $period,
            'category' => $category,
            'pdf' => false,
        ]);
    }

    /**
     * Export the complaint report as a PDF download.
     *
     * @return BinaryFileResponse
     */
    public function exportPdf()
    {
        [$complaints, $summary, $period, $category] = $this->reportData();

        $pdf = Pdf::loadView('admin.reports.complaints', [
            'complaints' => $complaints,
            'summary' => $summary,
            'period' => $period,
            'category' => $category,
            'pdf' => true,
        ])->setPaper('a4', 'landscape');

        // The saved file must say what window it covers; without a period the
        // name is exactly what it has always been.
        $name = $period !== null
            ? sprintf('laporan-pengaduan-%s-%s-%s.pdf', str_replace('-', '', $period['from']), str_replace('-', '', $period['to']), now()->format('Ymd-His'))
            : 'laporan-pengaduan-'.now()->format('Ymd-His').'.pdf';

        return $pdf->download($name);
    }

    /**
     * Build the filtered report data and its status summary.
     *
     * @return array{0: Collection<int, Complaint>, 1: array<string, int>, 2: ?array, 3: ?string}
     */
    private function reportData(): array
    {
        $period = Complaint::resolvePeriod();
        $category = Complaint::resolveCategory();

        // The same filters as the list (scopeSearch + scopeCategory), so the
        // printed window is exactly what "Cetak Sesuai Filter" promised — a
        // pelapor or ruang match prints, too, and a category match prints
        // under the same name the picker showed.
        $query = Complaint::query()
            ->period($period)
            ->search(request('q'))
            ->category($category)
            ->with(['user', 'category', 'room']);

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $complaints = $query->latest()->get();

        $summary = [
            'total' => $complaints->count(),
            'pending' => $complaints->where('status', 'pending')->count(),
            'in_progress' => $complaints->where('status', 'in_progress')->count(),
            'resolved' => $complaints->where('status', 'resolved')->count(),
            'rejected' => $complaints->where('status', 'rejected')->count(),
        ];

        return [$complaints, $summary, $period, $category !== null ? Category::find($category)?->name : null];
    }
}
