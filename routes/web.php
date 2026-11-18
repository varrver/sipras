<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ComplaintManagementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UsersCsvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Siswa\ComplaintController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Teknisi\TeknisiComplaintController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// The public entry: guests read the one-screen introduction; a signed-in
// visitor has no business on an intro — bounce to the shared dashboard
// controller, which routes by role.
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('landing');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('/master/categories', CategoryController::class)->names('master.categories');
        Route::resource('/master/rooms', RoomController::class)->names('master.rooms');
        // CSV round-trip comes BEFORE the resource: GET /master/users/export
        // must hit the export action, not the resource's show {user}.
        Route::get('/master/users/export', [UsersCsvController::class, 'export'])
            ->name('master.users.export');
        Route::post('/master/users/import', [UsersCsvController::class, 'import'])
            ->name('master.users.import');
        Route::resource('/master/users', UserController::class)->names('master.users');
        Route::get('/pengaduan', [ComplaintManagementController::class, 'index'])->name('pengaduan.index');
        Route::get('/pengaduan/laporan', [ComplaintManagementController::class, 'report'])->name('pengaduan.report');
        Route::get('/pengaduan/laporan/pdf', [ComplaintManagementController::class, 'exportPdf'])->name('pengaduan.report-pdf');
        Route::get('/pengaduan/{complaint}', [ComplaintManagementController::class, 'show'])->name('pengaduan.show');
        Route::patch('/pengaduan/{complaint}/status', [ComplaintManagementController::class, 'updateStatus'])->name('pengaduan.status');
    });

Route::prefix('teknisi')
    ->middleware(['auth', 'role:teknisi'])
    ->name('teknisi.')
    ->group(function () {
        Route::get('/pengaduan', [TeknisiComplaintController::class, 'index'])->name('pengaduan.index');
        Route::get('/pengaduan/{complaint}', [TeknisiComplaintController::class, 'show'])->name('pengaduan.show');
        Route::patch('/pengaduan/{complaint}/status', [TeknisiComplaintController::class, 'updateStatus'])->name('pengaduan.status');
    });

Route::prefix('siswa')
    ->middleware(['auth', 'role:siswa'])
    ->name('siswa.')
    ->group(function () {
        Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
        Route::get('/pengaduan/create', [ComplaintController::class, 'create'])->name('pengaduan.create');
        Route::post('/pengaduan', [ComplaintController::class, 'store'])->name('pengaduan.store');
        Route::get('/pengaduan', [ComplaintController::class, 'index'])->name('pengaduan.index');
        Route::get('/pengaduan/{complaint}', [ComplaintController::class, 'show'])->name('pengaduan.show');
        Route::delete('/pengaduan/{complaint}', [ComplaintController::class, 'destroy'])->name('pengaduan.destroy');
    });

require __DIR__.'/auth.php';

// Unmatched addresses have no route, so their 404 used to fall out of the router
// into the exception handler, which renders the card OUTSIDE the web middleware
// group: no session ever started, and every visitor — signed in or not — was
// offered "Masuk ke SIPRAS" instead of their own dashboard (see x-error-card's
// recovery CTA). This catch-all claims the address from inside the web group
// instead — withRouting wraps this whole file in Route::middleware('web') — so
// StartSession runs and the card can send each role to its own dashboard.
//
// Marked with the framework's own fallback flag, so it only ever matches when no
// other route does — including routes registered after boot (like the ones
// feature tests add at runtime). It registers every verb on purpose: a
// state-changing request to an unknown address answers 404 too (RegistrationTest
// posts to /register, an address that deliberately does not exist, and expects a
// 404). The price: a wrong verb on a KNOWN address also answers 404 instead of
// the framework's 405 — chosen here for one consistent "address not found"
// story, rather than two statuses depending on where the typo landed.
//
// CSRF is excluded so an unknown address always answers 404: without the
// exclusion, a token-less POST to a typo'd URL would be reported as a CSRF
// failure (419) — a session problem that does not exist — before the page could
// say the address simply isn't there. Every real route keeps CSRF enforcement.
Route::any('/{any}', function (Request $request) {
    // JSON clients keep the framework's JSON 404 (the same body and message the
    // router itself throws), mirroring shouldRenderJsonWhen in bootstrap/app.php;
    // only browsers get the rendered card.
    if ($request->is('api/*') || $request->expectsJson()) {
        throw new NotFoundHttpException(sprintf('The route %s could not be found.', $request->path()));
    }

    return response()->view('errors.404', [], 404);
})
    ->where('any', '.*')
    ->fallback()
    ->withoutMiddleware(PreventRequestForgery::class);
