<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        return view('admin.dashboard', [
            'counts' => Complaint::statusCounts(),
            'recentComplaints' => Complaint::with(['user', 'category', 'room'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
