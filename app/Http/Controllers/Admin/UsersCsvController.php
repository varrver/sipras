<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ImportUsersRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersCsvController extends Controller
{
    /**
     * Stream the whole roster as name,email,role — the same shape the import
     * accepts, so an admin can round-trip an edited file instead of guessing
     * a format. Header first, roster in id order for determinism.
     */
    public function export(): StreamedResponse
    {
        $filename = 'sipras-users-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['name', 'email', 'role']);

            User::query()->orderBy('id')->get(['name', 'email', 'role'])
                ->each(fn (User $user) => fputcsv($out, [$user->name, $user->email, $user->role]));

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * All-or-nothing import (the teacher's rule): the whole file is read and
     * every row validated FIRST; a single invalid row aborts the import with
     * a row-by-row report and zero writes. Valid files create in one
     * transaction with a random password (D3) that only an admin can reset.
     */
    public function import(ImportUsersRequest $request): RedirectResponse
    {
        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if ($handle === false) {
            // Covered by the FormRequest file rules, but a stream can still
            // fail to open — fail loudly, never as "zero rows imported".
            return back()->with('error', 'Berkas CSV tidak dapat dibaca.');
        }

        $header = fgetcsv($handle);
        // A UTF-8 BOM from Windows/Excel exports would glue to 'name'.
        $header = array_map(fn (string $value) => strtolower(trim($value, '﻿')), $header ?: []);

        if ($header !== ['name', 'email', 'role']) {
            fclose($handle);

            return back()->with('error', 'Berkas CSV tidak dikenali: baris pertama harus berupa header "name,email,role".');
        }

        // 'name' => 'Nama wajib diisi.' etc. — one label per collected failure,
        // quoted with the 1-based row number the header counts as row 1.
        $errors = [];

        // Database emails seed the seen-set so in-file duplicates AND existing
        // accounts fail on the same "sudah digunakan" message.
        $seenEmails = User::pluck('email')->all();

        $rows = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // fgetcsv yields [null] for a blank line — skip it, not a failure.
            if ($row === [null] || array_filter($row, fn ($cell) => trim((string) $cell) !== '') === []) {
                continue;
            }

            $cells = array_map(fn ($cell) => trim((string) $cell), array_pad($row, 3, ''));
            [$name, $email, $role] = $cells;
            $label = 'Baris '.$rowNumber;

            if ($name === '') {
                $errors[] = $label.': Nama wajib diisi.';
            }

            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = $label.': Email wajib diisi dan formatnya valid.';
            } elseif (in_array($email, $seenEmails, true)) {
                $errors[] = $label.': Email tersebut sudah digunakan.';
            } else {
                $seenEmails[] = $email;
            }

            if (! in_array($role, User::ROLES, true)) {
                $errors[] = $label.': Peran (role) tidak valid.';
            }

            $rows[] = compact('name', 'email', 'role');
        }

        fclose($handle);

        if ($errors !== []) {
            return back()->with('error', 'Impor dibatalkan — berkas berisi baris tidak valid, tidak ada pengguna yang dibuat. '
                .implode(' ', $errors));
        }

        $count = count($rows);

        // One transaction for the whole file (design D2): a row-level edge
        // cannot half-write the roster.
        DB::transaction(function () use ($rows): void {
            foreach ($rows as ['name' => $name, 'email' => $email, 'role' => $role]) {
                User::create([
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'password' => Hash::make(Str::random(24)),
                ]);
            }
        });

        session()->flash('success', $count.' pengguna berhasil diimpor. Sandi setiap akun diatur acak — '
            .'reset lewat halaman ini bila perlu.');

        return redirect()->route('admin.master.users.index');
    }
}
