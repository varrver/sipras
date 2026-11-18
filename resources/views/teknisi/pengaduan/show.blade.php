@section('title', $complaint->title)
<x-app-layout>
    <x-slot name="header">
        <x-complaint-header :complaint="$complaint"
            :back-href="route('teknisi.pengaduan.index')"
            :back-label="__('Kembali ke antrean')" />
    </x-slot>

    @php
        // Server-render the form for the status the page loaded in. A validation
        // error wins over the stored status so nothing the teknisi typed is lost,
        // and Alpine is seeded from the same value so the select and the
        // branches it drives never start out disagreeing.
        $notesStatus = old('status', $complaint->status);
        $notesIsRejection = $notesStatus === 'rejected';
        $notesTakesText = in_array($notesStatus, ['resolved', 'rejected'], true);
    @endphp

    <div class="pb-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card p-6 text-on-surface">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <span class="text-sm font-mono font-semibold text-on-surface">
                        <span class="sr-only">Nomor tiket: </span>{{ $complaint->ticket_number }}
                    </span>
                    <x-status-badge :status="$complaint->status" />
                </div>

                <x-complaint-info :complaint="$complaint" staff />

                <div class="mt-6">
                    <h2 class="font-medium text-on-surface">Linimasa</h2>
                    <x-status-timeline
                        class="mt-3"
                        :status="$complaint->status"
                        :created-at="$complaint->created_at"
                        :resolved-at="$complaint->resolved_at"
                    />
                </div>

                <div class="mt-6">
                    <h2 class="font-medium text-on-surface">Deskripsi</h2>
                    <p class="mt-1.5 text-on-surface whitespace-pre-line">{{ $complaint->description }}</p>
                </div>

                <x-photo-frame id="evidence-image"
                    :src="asset('storage/'.$complaint->evidence_image)"
                    :alt="__('Bukti pengaduan')"
                    :title="__('Foto Bukti')"
                    :fallback="__('Berkas foto bukti tidak ditemukan di penyimpanan.')"
                    loading />

                @if ($complaint->status === 'resolved' && $complaint->resolved_image)
                    <x-photo-frame id="resolved-image"
                        :src="asset('storage/'.$complaint->resolved_image)"
                        :alt="__('Dokumentasi perbaikan')"
                        :title="__('Dokumentasi Perbaikan')"
                        :fallback="__('Berkas dokumentasi perbaikan tidak ditemukan di penyimpanan.')"
                        loading />
                @endif

                @php
                    $decisionTint = $complaint->status === 'rejected'
                        ? 'bg-error-container text-on-error-container'
                        : 'bg-secondary-container text-on-secondary-container';
                    $decisionLabel = $complaint->status === 'rejected'
                        ? __('Alasan Penolakan')
                        : __('Catatan Admin');
                @endphp
                @if ($complaint->admin_notes)
                    <div class="mt-6 rounded-xl p-4 {{ $decisionTint }}">
                        <h3 class="font-medium">{{ $decisionLabel }}</h3>
                        <p class="mt-1.5 whitespace-pre-line">{{ $complaint->admin_notes }}</p>
                    </div>
                @endif
            </div>

            {{-- The status work lives here, on the teknisi detail page — never
                 on a list. Once the ticket is final the card disappears
                 entirely: a finished or rejected complaint accepts no further
                 changes, so the page stops offering what the record refuses. --}}
            @unless (in_array($complaint->status, ['resolved', 'rejected'], true))
                <div class="card mt-5">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-on-surface">Perbarui Status</h2>

                        <form method="POST" action="{{ route('teknisi.pengaduan.status', $complaint) }}" enctype="multipart/form-data"
                            class="mt-4 space-y-5"
                            x-data="{
                                formStatus: '{{ $notesStatus }}',
                                originalStatus: '{{ $complaint->status }}',
                                submitting: false,
                                confirmReason: '',
                                branchAnnounce: '',
                                needsConfirm() {
                                    // Entering Ditolak commits the terminal decision in
                                    // the other direction — the typed reason is what
                                    // the student reads as final — so it asks the
                                    // same question the ended states ask.
                                    if (this.originalStatus !== 'rejected' && this.formStatus === 'rejected') return 'enter-rejected'
                                    const hasNewProof = document.getElementById('resolved_image')?.files?.length > 0
                                    return ''
                                },
                            }"
                            x-init="$watch('formStatus', value => branchAnnounce = value === 'rejected' ? 'Kolom Alasan Penolakan muncul — wajib diisi saat menolak.' : value === 'resolved' ? 'Kolom Catatan Admin dan Dokumentasi Perbaikan muncul.' : '')"
                            @submit="
                                const reason = needsConfirm()
                                if (reason && $event.submitter?.dataset?.confirm !== '1') {
                                    confirmReason = reason
                                    $event.preventDefault()
                                    $dispatch('open-modal', 'confirm-status-change')
                                    return
                                }
                                submitting = true
                            ">
                            @csrf
                            @method('PATCH')

                            <p class="sr-only" aria-live="polite" x-text="branchAnnounce"></p>

                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                {{-- Same contract as the admin form, same in-page
                                     picker: the teknisi has full process authority.
                                     The technology underneath (x-select-menu) is the
                                     whole layout's own, so the form's structure
                                     never depends on a native select. --}}
                                <x-select-menu id="status" name="status" class="mt-1.5"
                                    :value="$notesStatus"
                                    nativeLabel="Status"
                                    :options="collect(['in_progress', 'resolved', 'rejected'])
                                        ->map(fn ($status) => ['value' => $status, 'label' => \App\Models\Complaint::STATUS_LABELS[$status]])
                                        ->all()"
                                    x-on:changed="formStatus = $event.detail.value" />
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                <p id="status-effect" class="mt-2 text-xs text-on-surface-variant">
                                    <span x-show="formStatus === 'in_progress'"
                                        @unless ($notesStatus === 'in_progress') style="display: none" @endunless>
                                        Dalam Proses — perbaikan sedang berjalan; siswa melihat progres tiket ini.
                                    </span>
                                    <span x-show="formStatus === 'resolved'"
                                        @unless ($notesStatus === 'resolved') style="display: none" @endunless>
                                        Selesai — menyimpan mengakhiri tiket dengan waktu selesai dan fotonya, lalu siswa membaca hasil perbaikannya.
                                    </span>
                                    <span x-show="formStatus === 'rejected'"
                                        @unless ($notesStatus === 'rejected') style="display: none" @endunless>
                                        Ditolak — alasan di bawah akan dibaca siswa sebagai keputusan akhir pengaduan ini.
                                    </span>
                                </p>
                            </div>

                            <div x-show="['resolved', 'rejected'].includes(formStatus)" x-cloak
                                @unless ($notesTakesText) style="display: none" @endunless>
                                {{-- Exactly one <label> for the control, switched
                                     live and seeded server-side, mirroring the
                                     admin form's own one-label rule. --}}
                                <label for="admin_notes" class="block text-sm font-medium text-on-surface"
                                    x-text="formStatus === 'rejected' ? '{{ __('Alasan Penolakan') }}' : '{{ __('Catatan Teknisi (opsional)') }}'">{{ $notesIsRejection ? __('Alasan Penolakan') : __('Catatan Teknisi (opsional)') }}</label>
                                <textarea id="admin_notes" name="admin_notes" rows="4" class="field mt-1.5"
                                    aria-describedby="admin_notes_hint"
                                    x-bind:required="formStatus === 'rejected'"
                                    @if ($notesIsRejection) required @endif
                                    x-bind:placeholder="formStatus === 'rejected' ? 'Contoh: kerusakan bukan pada sarana sekolah, melainkan pada jaringan listrik.' : 'Contoh: keran sudah diganti dan sedang diuji tekanannya.'">{{ old('admin_notes', $complaint->admin_notes) }}</textarea>
                                <p id="admin_notes_hint" class="mt-2 text-xs text-on-surface-variant">
                                    <span x-show="formStatus === 'rejected'"
                                        @unless ($notesIsRejection) style="display: none" @endunless>
                                        Wajib diisi saat menolak — alasan ini akan dibaca siswa, jadi tuliskan kenapa pengaduan ini tidak bisa ditindaklanjuti.
                                    </span>
                                    <span x-show="formStatus !== 'rejected'"
                                        @if ($notesIsRejection) style="display: none" @endif>
                                        Opsional. Isi kalau ada hal yang perlu diketahui siswa soal perbaikannya.
                                    </span>
                                    @if ($complaint->admin_notes)
                                        <span x-show="['resolved', 'rejected'].includes(formStatus)"
                                            @unless ($notesTakesText) style="display: none" @endunless
                                            class="block mt-1">
                                            Catatan tersimpan tampil pada kartu; menyimpan memperbaruinya.
                                        </span>
                                    @endif
                                </p>
                                <x-input-error :messages="$errors->get('admin_notes')" class="mt-2" />
                            </div>

                            <div x-show="formStatus === 'resolved'" x-cloak
                                @unless ($notesStatus === 'resolved') style="display: none" @endunless>
                                <x-input-label for="resolved_image" :value="__('Dokumentasi Perbaikan')" />
                                {{-- Same authored capsule as the other upload fields:
                                     the trigger speaks Indonesian instead of the
                                     browser's "Choose File"; the sr-only input
                                     keeps native behaviour and its hidden-then-
                                     revealed branch semantics untouched. --}}
                                <label x-data="{ fileLabel: 'Pilih dokumentasi…' }"
                                    class="file-field mt-1.5">
                                    <input id="resolved_image" name="resolved_image" type="file" accept="image/jpeg,image/png"
                                        aria-describedby="resolved_image_hint" class="sr-only"
                                        @change="fileLabel = $event.target.files[0]?.name ?? 'Pilih dokumentasi…'" />
                                    <svg class="h-4.5 w-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                    </svg>
                                    <span class="truncate" x-text="fileLabel">Pilih dokumentasi…</span>
                                </label>
                                <p id="resolved_image_hint" class="mt-2 text-xs text-on-surface-variant">
                                    Wajib. Foto bukti perbaikan yang sudah selesai — JPG atau PNG, maksimal 2 MB.
                                </p>
                                <x-input-error :messages="$errors->get('resolved_image')" class="mt-2" />
                            </div>

                            <div>
                                <x-primary-button x-bind:disabled="submitting">
                                    <span x-show="!submitting">{{ __('Perbarui Status') }}</span>
                                    <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2" aria-live="polite">
                                        <x-spinner class="h-4 w-4 animate-spin" />
                                        {{ __('Menyimpan…') }}
                                    </span>
                                </x-primary-button>
                            </div>

                            <p class="text-xs text-on-surface-variant">
                                Diperbarui pada {{ $complaint->updated_at->translatedFormat('d F Y, H:i') }}
                            </p>

                            {{-- Entering Ditolak never submits blind: the typed
                                 reason is the record the student reads as final.
                                 Only the modal's confirm click carries
                                 data-confirm="1" and may submit. --}}
                            <x-modal name="confirm-status-change" maxWidth="md" focusable>
                                <div class="p-6">
                                    <h2 id="confirm-status-change-title" class="text-lg font-semibold text-on-surface">
                                        {{ __('Konfirmasi Perubahan Status') }}
                                    </h2>
                                    <p x-show="confirmReason === 'enter-rejected'" x-cloak class="mt-1 text-sm text-on-surface-variant">
                                        Pengaduan akan ditolak. Alasan yang diketik di bawah akan dibaca siswa sebagai keputusan akhir — lanjutkan hanya jika keputusannya sudah pasti.
                                    </p>
                                    <div class="mt-6 flex justify-end gap-2">
                                        <button type="button" class="btn-secondary" @click="$dispatch('close')">
                                            {{ __('Batal') }}
                                        </button>
                                        <x-primary-button type="submit" data-confirm="1" x-bind:disabled="submitting">
                                            <span x-show="!submitting">{{ __('Ya, Lanjutkan') }}</span>
                                            <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2" aria-live="polite">
                                                <x-spinner class="h-4 w-4 animate-spin" />
                                                {{ __('Menyimpan…') }}
                                            </span>
                                        </x-primary-button>
                                    </div>
                                </div>
                            </x-modal>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>
</x-app-layout>
