@section('title', $complaint->title)
<x-app-layout>
    <x-slot name="header">
        <x-complaint-header :complaint="$complaint"
            :back-href="route('admin.pengaduan.index')"
            :back-label="__('Kembali ke daftar')" />
    </x-slot>

    @php
        // Server-render the form for the status the page loaded in. A validation
        // error wins over the stored status so nothing the admin typed is lost,
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

                {{-- Same trace the student sees, so an admin reading a ticket can tell at a
                     glance how far it got without reconstructing it from timestamps. --}}
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
                    // The stored decision is part of the record, not only the edit
                    // form: reading a ticket weeks later should answer "how did it
                    // end and why" without opening the form's textarea. The
                    // labelling and tints mirror the student's own panel — same
                    // words, same §3 semantics for ended states.
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

            <div class="card mt-5">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-on-surface">Perbarui Status</h2>

                    <form method="POST" action="{{ route('admin.pengaduan.status', $complaint) }}" enctype="multipart/form-data"
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
                                if (this.originalStatus === 'rejected' && this.formStatus !== 'rejected') return 'leave-rejected'
                                if (this.originalStatus === 'resolved' && this.formStatus !== 'resolved') return 'leave-resolved'
                                if (this.originalStatus === 'resolved' && this.formStatus === 'resolved' && hasNewProof) return 'replace-proof'
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

                        {{-- Picking a status reveals whole sections client-side
                             (and un-reveals others); assistive tech hears one
                             polite sentence naming what appeared. The
                             server-rendered branch needs no announcement —
                             nothing moved for the page it loaded as. --}}
                        <p class="sr-only" aria-live="polite" x-text="branchAnnounce"></p>

                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            {{-- Options built from the model's status list and label map, so
                                 this picker can never offer a status the rest of the app does
                                 not name, or miss one it does. The menu is rendered in-page
                                 (x-select-menu) and reports each choice to formStatus via
                                 `changed`: every x-show branch above keeps booting off it
                                 exactly as when a native select fed x-model. --}}
                            <x-select-menu id="status" name="status" class="mt-1.5"
                                :value="$notesStatus"
                                nativeLabel="Status"
                                :options="collect(\App\Models\Complaint::STATUSES)
                                    ->map(fn ($status) => ['value' => $status, 'label' => \App\Models\Complaint::STATUS_LABELS[$status]])
                                    ->all()"
                                x-on:changed="formStatus = $event.detail.value" />
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            {{-- Consequence copy: the label names the state, this
                                 sentence names what saving with it commits to.
                                 All four render server-side (one per destination),
                                 so the page never needs Alpine booted to explain
                                 a state it already displays. --}}
                            <p id="status-effect" class="mt-2 text-xs text-on-surface-variant">
                                <span x-show="formStatus === 'pending'"
                                    @unless ($notesStatus === 'pending') style="display: none" @endunless>
                                    Menunggu — pengaduan sudah masuk dan menunggu tinjauan sarana.
                                </span>
                                <span x-show="formStatus === 'in_progress'"
                                    @unless ($notesStatus === 'in_progress') style="display: none" @endunless>
                                    Dalam Proses — perbaikan sedang berjalan; siswa melihat progres tiket ini.
                                </span>
                                <span x-show="formStatus === 'resolved'"
                                    @unless ($notesStatus === 'resolved') style="display: none" @endunless>
                                    Selesai — menyimpan mengakhiri tiket, mencatat waktu selesai, dan membuka kolom dokumentasi perbaikan di bawah.
                                </span>
                                <span x-show="formStatus === 'rejected'"
                                    @unless ($notesStatus === 'rejected') style="display: none" @endunless>
                                    Ditolak — alasan di bawah akan dibaca siswa sebagai keputusan akhir pengaduan ini.
                                </span>
                            </p>
                        </div>

                        {{-- The rule lives in the form, not only in a 422: which statuses take a
                             note, which note is required, and what it is used for.

                             Every branch below is rendered server-side for the status the page
                             loaded in, and Alpine only takes over once the admin changes the
                             select. Without that, an un-booted page shows the wrong label
                             (and "wajib diisi" on a Menunggu complaint, which isn't true). --}}
                        {{-- One binding per branch, on a plain wrapper: a directive written
                             inside an <x-…> tag makes Blade emit that tag verbatim, which
                             would drop the label element altogether. --}}
                        <div x-show="['resolved', 'rejected'].includes(formStatus)" x-cloak
                            @unless ($notesTakesText) style="display: none" @endunless>
                            {{-- Exactly one label for one control: two <label for="admin_notes">
                                 elements made axe read the control as unlabelled. The text is
                                 server-rendered for the loaded status (so it is correct before
                                 Alpine boots) and `x-text` switches it live. A plain <label> is
                                 used because a directive on <x-input-label> would make Blade
                                 emit the component tag verbatim and drop the element. --}}
                            <label for="admin_notes" class="block text-sm font-medium text-on-surface"
                                x-text="formStatus === 'rejected' ? '{{ __('Alasan Penolakan') }}' : '{{ __('Catatan Admin (opsional)') }}'">{{ $notesIsRejection ? __('Alasan Penolakan') : __('Catatan Admin (opsional)') }}</label>
                            <textarea id="admin_notes" name="admin_notes" rows="4" class="field mt-1.5"
                                aria-describedby="admin_notes_hint"
                                x-bind:required="formStatus === 'rejected'"
                                @if ($notesIsRejection) required @endif
                                x-bind:placeholder="formStatus === 'rejected' ? 'Contoh: kerusakan bukan pada sarana sekolah, melainkan pada jaringan listrik.' : 'Contoh: proyektor sudah diganti dan siap dipakai.'">{{ old('admin_notes', $complaint->admin_notes) }}</textarea>
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
                            {{-- Same authored capsule as the siswa form: the
                                 trigger speaks Indonesian; the sr-only input
                                 keeps its id (the guard test selects it) and
                                 its browser-enforced validation behaviour. --}}
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
                                Opsional. JPG atau PNG, maksimal 2 MB.
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

                        {{-- Closes the status card the same way the record's other
                             stamps read: real data only, named like the rest. --}}
                        <p class="text-xs text-on-surface-variant">
                            Diperbarui pada {{ $complaint->updated_at->translatedFormat('d F Y, H:i') }}
                        </p>

                        {{-- The consequential transitions never submit blind:
                             leaving an ended state reopens a finished ticket for
                             the student, a new proof silently replaces the
                             stored one, and entering Ditolak writes the typed
                             reason into a record the student reads as final.
                             The confirm is stateless — only a click
                             whose submitter carries data-confirm="1" passes the
                             guard, so a cancelled dialog cannot leave a
                             bypassable reminder behind. The modal lives inside
                             this form because its confirm button submits it. --}}
                        <x-modal name="confirm-status-change" maxWidth="md" focusable>
                            <div class="p-6">
                                {{-- `id` per the modal component contract: it is
                                     what the dialog's aria-labelledby points at. --}}
                                <h2 id="confirm-status-change-title" class="text-lg font-semibold text-on-surface">
                                    {{ __('Konfirmasi Perubahan Status') }}
                                </h2>
                                <p x-show="confirmReason === 'enter-rejected'" x-cloak class="mt-1 text-sm text-on-surface-variant">
                                    Pengaduan akan ditolak. Alasan yang diketik di bawah akan dibaca siswa sebagai keputusan akhir — lanjutkan hanya jika keputusannya sudah pasti.
                                </p>
                                <p x-show="confirmReason === 'leave-rejected'" x-cloak class="mt-1 text-sm text-on-surface-variant">
                                    Pengaduan ini sudah ditolak. Mengubah status akan membuka kembali tiket yang sudah berakhir — lanjutkan hanya jika keputusannya memang perlu ditinjau ulang.
                                </p>
                                <p x-show="confirmReason === 'leave-resolved'" x-cloak class="mt-1 text-sm text-on-surface-variant">
                                    Pengaduan ini sudah selesai. Mengubah status akan membuka kembali tiket yang sudah berakhir di daftar kerja.
                                </p>
                                <p x-show="confirmReason === 'replace-proof'" x-cloak class="mt-1 text-sm text-on-surface-variant">
                                    Foto dokumentasi yang tersimpan akan diganti penuh oleh berkas baru yang dipilih.
                                </p>
                                <div class="mt-6 flex justify-end gap-2">
                                    <button type="button" class="btn-secondary" @click="$dispatch('close')">
                                        {{ __('Batal') }}
                                    </button>
{{-- Hand-built class via the shared component: the destructive branch
                                         rebinds this button's class live (§2.5,
                                         x-bind: never the bare shorthand), and
                                         `.btn-error` wins the cascade over the
                                         component's static `btn-primary`. --}}
                                    <x-primary-button type="submit" data-confirm="1" x-bind:disabled="submitting"
                                        x-bind:class="confirmReason === 'leave-rejected' ? 'btn-error' : ''">
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
        </div>
    </div>
</x-app-layout>