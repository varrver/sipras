<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Redirect the user to their role's dashboard. Teknisi has no dashboard of
     * its own — the queue is their home (the spec keeps dashboard-wide counts
     * out of the teknisi surface) — so they land in their worklist directly.
     */
    public function index()
    {
        return match (auth()->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'teknisi' => redirect()->route('teknisi.pengaduan.index'),
            default => redirect()->route('siswa.dashboard'),
        };
    }
}
