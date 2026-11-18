@section('title', __('Manajemen Ruangan'))
<x-app-layout>
    <x-slot name="header">
<div class="page-heading-row">
            <h1 class="text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Manajemen Ruangan') }}
            </h1>
            <a href="{{ route('admin.master.rooms.create') }}" class="btn-primary">
                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('Tambah Ruangan') }}
            </a>
        </div>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="card">
                <div class="p-6">
                    <p class="mb-4 text-sm text-on-surface-variant">Daftar ruangan sekolah.</p>

                    @if ($rooms->isEmpty())
                        <p class="text-on-surface-variant">Belum ada ruangan.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-outline-variant">
                                {{-- A name lets AT jump to the table and list it without
                                     relying on the surrounding prose. `sr-only`, not a
                                     visible caption: the intro paragraph already says it. --}}
                                <caption class="sr-only">Daftar ruangan sekolah</caption>
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Nama</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-on-surface-variant uppercase tracking-wider">Kode</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-on-surface-variant uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-surface-container divide-y divide-outline-variant">
                                    @foreach ($rooms as $room)
                                        <tr class="transition-colors hover:bg-surface-container-high">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface-variant">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-on-surface">{{ $room->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface-variant">{{ $room->code }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('admin.master.rooms.edit', $room) }}"
                                                    class="inline-flex items-center gap-1 font-semibold text-primary hover:text-primary/80">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                    Edit
                                                </a>
                                                <button type="button" class="ml-3 inline-flex items-center gap-1 font-semibold text-error hover:text-error/80"
                                                    x-data x-on:click="$dispatch('open-modal', 'confirm-room-delete-{{ $room->id }}')">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                    Hapus
                                                </button>

                                                {{-- CSP-safe confirm: the in-row native confirm() is
                                                     replaced by the shared modal component. One modal
                                                     per row (id-suffixed name) because the destructive
                                                     action must name its target; a room still in use
                                                     is refused by the DB (restrict) and surfaces as
                                                     the standard error flash. --}}
                                                <x-modal name="confirm-room-delete-{{ $room->id }}" maxWidth="md">
                                                    <form action="{{ route('admin.master.rooms.destroy', $room) }}" method="POST" class="p-6">
                                                        @csrf
                                                        @method('DELETE')
                                                        <h2 id="confirm-room-delete-{{ $room->id }}-title" class="text-lg font-semibold text-on-surface">
                                                            {{ __('Hapus ruangan ini?') }}
                                                        </h2>
                                                        <p class="mt-1 text-sm text-on-surface-variant">
                                                            {{ __('Ruangan :name akan dihapus permanen. Ruangan yang masih dipakai pengaduan tidak dapat dihapus.', ['name' => $room->name]) }}
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
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>