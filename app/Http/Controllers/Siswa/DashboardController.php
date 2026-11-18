<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Show the siswa dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        return view('siswa.dashboard', [
            'totalPengaduan' => $user->complaints()->count(),
            'menunggu' => $user->complaints()->where('status', 'pending')->count(),
            'proses' => $user->complaints()->where('status', 'in_progress')->count(),
            'selesai' => $user->complaints()->where('status', 'resolved')->count(),
            'ditolak' => $user->complaints()->where('status', 'rejected')->count(),
            'recentComplaints' => $user->complaints()
                ->with(['category', 'room'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
