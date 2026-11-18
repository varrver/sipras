@section('title', __('Manajemen Pengguna'))
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading-row">
            <h1 class="text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Manajemen Pengguna') }}
            </h1>
            <a href="{{ route('admin.master.users.create') }}" class="btn-primary">
                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('Tambah Pengguna') }}
            </a>
        </div>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="card">
                <div class="p-6">
                    <p class="mb-4 text-sm text-on-surface-variant">Daftar pengguna aplikasi.</p>

                    <div class="mb-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.master.users.export') }}"
                            class="btn-secondary inline-flex items-center gap-2" download>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v13.5m0 0-4.5-4.5M12 16.5l4.5-4.5M4.5 20.25h15" />
                            </svg>
                            {{ __('Ekspor CSV') }}
                        </a>
                        <button type="button" class="btn-secondary inline-flex items-center gap-2"
                            x-data x-on:click="$dispatch('open-modal', 'import-users')">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V3m0 0-4.5 4.5M12 3l4.5 4.5M4.5 20.25h15" />
                            </svg>
                            {{ __('Impor CSV') }}
                        </button>
                    </div>

                    @if ($users->isEmpty())
                        <p class="text-on-surface-variant">Belum ada pengguna.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-outline-variant">
                                {{-- A name lets AT jump to the table and list it without
                                     relying on the surrounding prose. `sr-only`, not a
                                     visible caption: the intro paragraph already says it. --}}
                                <caption class="sr-only">Daftar pengguna aplikasi</caption>
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Nama</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Peran (Role)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Pengaduan</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-on-surface-variant uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-surface-container divide-y divide-outline-variant">
                                    @foreach ($users as $user)
                                        <tr class="transition-colors hover:bg-surface-container-high">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface-variant">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-on-surface">{{ $user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface-variant">{{ $user->email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($user->role === 'admin')
                                                    <span class="chip bg-secondary-container text-on-surface">Admin</span>
                                                @elseif ($user->role === 'teknisi')
                                                    <span class="chip bg-tertiary-container text-on-tertiary-container">Teknisi</span>
                                                @else
                                                    <span class="chip bg-secondary-container text-on-secondary-container">Siswa</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface-variant">{{ $user->complaints_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('admin.master.users.edit', $user) }}"
                                                    class="inline-flex items-center gap-1 font-semibold text-primary hover:text-primary/80">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                    Edit
                                                </a>
                                                @if (!$user->is(auth()->user()))
                                                    <button type="button" class="ml-3 inline-flex items-center gap-1 font-semibold text-error hover:text-error/80"
                                                        x-data x-on:click="$dispatch('open-modal', 'confirm-user-delete-{{ $user->id }}')">
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                        Hapus
                                                    </button>

                                                    {{-- CSP-safe confirm: the in-row native confirm() is
                                                         replaced by the shared modal component. One
                                                         modal per row (id-suffixed name) because the
                                                         destructive action must name its target. --}}
                                                    <x-modal name="confirm-user-delete-{{ $user->id }}" maxWidth="md">
                                                        <form action="{{ route('admin.master.users.destroy', $user) }}" method="POST" class="p-6">
                                                            @csrf
                                                            @method('DELETE')
                                                            <h2 id="confirm-user-delete-{{ $user->id }}-title" class="text-lg font-semibold text-on-surface">
                                                                {{ __('Hapus pengguna ini?') }}
                                                            </h2>
                                                            {{-- Name the scale: the cascade is by design (PRD),
                                                                 but an admin confirming blind cannot tell an
                                                                 account with 0 complaints from one with 47. --}}
                                                            <p class="mt-1 text-sm text-on-surface-variant">
                                                                @if (($user->complaints_count ?? 0) > 0)
                                                                    {{ __('Akun :name akan dihapus permanen beserta :count pengaduan yang pernah dibuat. Tindakan ini tidak dapat dibatalkan.', ['name' => $user->name, 'count' => $user->complaints_count]) }}
                                                                @else
                                                                    {{ __('Akun :name akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.', ['name' => $user->name]) }}
                                                                @endif
                                                            </p>
                                                            <div class="mt-6 flex justify-end gap-2">
                                                                <button type="button" class="btn-secondary" @click="$dispatch('close')">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn-error">
                                                                    {{ __('Ya, Hapus') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </x-modal>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            <x-modal name="import-users" maxWidth="md">
                <form action="{{ route('admin.master.users.import') }}" method="POST"
                    enctype="multipart/form-data" class="p-6">
                    @csrf
                    <h2 id="import-users-title" class="text-lg font-semibold text-on-surface">
                        {{ __('Impor pengguna dari CSV') }}
                    </h2>
                    {{-- The whole contract said aloud, since all-or-nothing and
                         the random password are the admin's expectations to set
                         before any file is uploaded. --}}
                    <p class="mt-1 text-sm text-on-surface-variant">
                        {{ __('Format kolom name,email,role (baris pertama berupa header). Bila ada satu baris pun tidak valid, seluruh berkas ditolak — tidak pernah ada impor sebagian. Sandi tiap akun baru diatur acak dan dapat direset oleh admin.') }}
                    </p>
                    <div class="mt-4">
                        <x-input-label for="csv_file" :value="__('Berkas CSV')" />
                        {{-- Same authored capsule as the other upload fields: the
                             trigger speaks Indonesian instead of the browser's
                             "Choose File"; the sr-only input keeps native
                             required and its browser bubble. --}}
                        <label x-data="{ fileLabel: 'Pilih berkas CSV…' }"
                            class="file-field mt-1.5">
                            <input id="csv_file" name="csv_file" type="file" required
                                accept=".csv,text/csv" class="sr-only"
                                @change="fileLabel = $event.target.files[0]?.name ?? 'Pilih berkas CSV…'" />
                            <svg class="h-4.5 w-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                            <span class="truncate" x-text="fileLabel">Pilih berkas CSV…</span>
                        </label>
                        @error('csv_file')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" class="btn-secondary" @click="$dispatch('close')">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn-primary">
                            {{ __('Impor') }}
                        </button>
                    </div>
                </form>
            </x-modal>
        </div>
    </div>
</x-app-layout>