<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    /**
     * Display a listing of the user's complaints.
     *
     * Filtering mirrors the admin list — one capsule status picker, one search
     * box — but scoped to the signed-in student. There are no counts on the
     * list: the student's dashboard cards are where their tallies live, so the
     * picker lists the vocabulary only, like the admin menu does.
     */
    public function index()
    {
        // The list's keyword scope.
        $applySearch = function ($query, $term): void {
            $query->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('ticket_number', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        };

        $query = auth()->user()->complaints()
            ->with(['category', 'room'])
            // An unrecognised status filters nothing rather than erroring: it
            // matches the admin list's behaviour and a hand-edited URL is a
            // reading mistake, not an input the student typed into a form.
            ->when(in_array(request('status'), Complaint::STATUSES, true), function ($query) {
                $query->where('status', request('status'));
            })
            ->when(request('q'), $applySearch);

        $complaints = $query->latest()->paginate(12)->withQueryString();

        // First-run onboarding is an account state, not a search result: a
        // no-match search must land on the filtered-empty copy, never read as
        // an empty account.
        $hasComplaints = auth()->user()->complaints()->exists();

        return view('siswa.pengaduan.index', compact('complaints', 'hasComplaints'));
    }

    /**
     * Show the form for creating a new complaint.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        // Code order, not name order: alphabetical-on-name floats the numbered
        // classrooms (the rooms a student is sitting in when something breaks)
        // to the bottom of a 105-option list. Code order keeps them in
        // plan-row order and puts them first.
        $rooms = Room::orderBy('code')->get();

        // Fixed group order, not first-seen: an alphabetical code sweep
        // scatters the labels (`BL`…, `LPF`…, `R-1…`), while the form should
        // read top-down like the floor plan — classrooms, labs, workshops,
        // then offices. groupLabel() classifies; this list only decides order.
        $roomGroups = collect(Room::CREATE_FORM_GROUPS)
            ->mapWithKeys(fn (string $label) => [
                $label => $rooms->filter(fn (Room $room) => $room->groupLabel() === $label)->values(),
            ]);

        return view('siswa.pengaduan.create', compact('categories', 'roomGroups'));
    }

    /**
     * Store a newly created complaint.
     */
    public function store(StoreComplaintRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['evidence_image'] = $request->file('evidence_image')->store('complaints/evidence', 'public');

        $complaint = Complaint::create($data);

        // The flash quotes the ticket the model just generated: the submission's
        // last visible moment is the one number the admin will ask for.
        session()->flash('success', "Pengaduan {$complaint->ticket_number} berhasil dikirim dan sedang menunggu verifikasi admin.");

        return redirect()->route('siswa.pengaduan.index', ['created' => $complaint->ticket_number]);
    }

    /**
     * Display the specified complaint.
     */
    public function show(Complaint $complaint)
    {
        abort_if($complaint->user_id !== auth()->id(), 403);

        return view('siswa.pengaduan.show', compact('complaint'));
    }

    /**
     * Withdraw the reporter's own still-pending complaint.
     *
     * The status guard is the product rule, not a safety net: once an admin
     * has picked the complaint up, the student's window is closed even if
     * the detail page was still open — so the answer is redirected, flash and
     * back, never a 500. Order matters: ownership first (leaks nothing to an
     * intruder), then status.
     */
    public function destroy(Complaint $complaint)
    {
        abort_if($complaint->user_id !== auth()->id(), 403);

        if ($complaint->status !== 'pending') {
            return redirect()
                ->route('siswa.pengaduan.show', $complaint)
                ->with('error', 'Pengaduan tidak dapat dibatalkan lagi karena sudah ditangani admin.');
        }

        // Eloquent delete, not a query delete, and inside the transaction:
        // the image unlink must belong to the same commit boundary as the row
        // removal so a rollback cannot leave a row without its file.
        DB::transaction(function () use ($complaint): void {
            $complaint->delete();

            Storage::disk('public')->delete(array_filter([
                $complaint->evidence_image,
                $complaint->resolved_image,
            ]));
        });

        session()->flash('success', 'Pengaduan '.$complaint->ticket_number.' berhasil dibatalkan.');

        return redirect()->route('siswa.pengaduan.index');
    }
}
