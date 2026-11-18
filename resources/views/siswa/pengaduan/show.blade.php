@section('title', $complaint->title)
<x-app-layout>
    <x-slot name="header">
        <x-complaint-header :complaint="$complaint"
            :back-href="route('siswa.pengaduan.index')"
            :back-label="__('Kembali ke pengaduan saya')" />
    </x-slot>

    <div class="pb-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card p-6 text-on-surface">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <span class="text-sm font-mono font-semibold text-on-surface">
                        {{-- First-timers don't know this chip is the quotable reference;
                             name it for screen readers and search-in-page without
                             adding visible noise to the header row. --}}
                        <span class="sr-only">Nomor tiket: </span>{{ $complaint->ticket_number }}
                    </span>
                    <x-status-badge :status="$complaint->status" />
                </div>

                <x-complaint-info :complaint="$complaint" />

                <div class="mt-6">
                    {{-- Card section labels are h2: the page's h1 is the ticket
                         title, and h3 labels under it skipped a level (a
                         screen reader navigating by headings fell from the
                         title straight into a third-level section). --}}
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
                    :fallback="__('Foto bukti tidak dapat ditampilkan.')"
                    loading />

                @if ($complaint->status === 'pending')
                    <div class="mt-6 flex items-start gap-3 rounded-xl bg-warning-container p-4 text-on-warning-container">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <div class="text-sm flex-1">
                            <p>Terima kasih sudah melapor! Tim Sarana Sekolah sedang meninjau pengaduan Anda.</p>
                            {{-- Salah kirim ruangan, salah ketik judul, atau terkirim
                                 dua kali: selama status masih Menunggu, pelapor tetap
                                 pemilik pengaduan dan bisa menariknya kembali. Begitu
                                 admin mengambil (status berubah), aksi ini hilang dari
                                 halaman — bukan sekadar dikunci di server. --}}
                            {{-- A capsule, not an underlined link: measured 20px tall
                                 at 390px and sits ~1300px down the page — under the
                                 WCAG 2.5.8 minimum for a destructive-intent control.
                                 btn-outline carries the app's 44px min-h touch box — and
                                 keeps the trigger visibly "a real action" (blue label on
                                 surface with a hairline border) without scaring a red or
                                 indigo hue onto a success banner, and without spending the
                                 danger hue at rest where the modal's btn-error needs it. --}}
                            <div class="mt-3 flex justify-end">
                                <button type="button" class="btn-outline"
                                    x-data x-on:click="$dispatch('open-modal', 'confirm-complaint-withdraw-{{ $complaint->id }}')">
                                    Batalkan pengaduan
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Destructive confirms use the shared modal, never confirm()
                         (DESIGN.md §3); id-suffixed name so the dialog can name its
                         exact ticket. Form uses the capsule btn-error, matching the
                         room/category/user delete dialogs. --}}
                    <x-modal name="confirm-complaint-withdraw-{{ $complaint->id }}" maxWidth="md" focusable>
                        <form action="{{ route('siswa.pengaduan.destroy', $complaint) }}" method="POST" class="p-6">
                            @csrf
                            @method('DELETE')
                            <h2 id="confirm-complaint-withdraw-{{ $complaint->id }}-title" class="text-lg font-semibold text-on-surface">
                                {{ __('Batalkan pengaduan ini?') }}
                            </h2>
                            <p class="mt-1 text-sm text-on-surface-variant">
                                {{ __('Pengaduan :ticket akan dihapus permanen beserta foto buktinya. Pengecekan yang sedang berjalan tidak akan ada — jika masih terjadi, ajukan kembali. Tindakan ini tidak dapat dibatalkan.', ['ticket' => $complaint->ticket_number]) }}
                            </p>
                            <div class="mt-6 flex justify-end gap-2">
                                {{-- 'Batal', not 'Kembali': 'Kembali' reads as the header's
                                     back-to-list arrow; every other destructive dialog in
                                     the app cancels with this same word. --}}
                                <button type="button" class="btn-secondary" @click="$dispatch('close')">
                                    {{ __('Batal') }}
                                </button>
                                <button type="submit" class="btn-error">
                                    {{ __('Ya, Batalkan') }}
                                </button>
                            </div>
                        </form>
                    </x-modal>
                @endif

                @if ($complaint->status === 'in_progress')
                    <div class="mt-6 flex items-start gap-3 rounded-xl bg-tertiary-container p-4 text-on-tertiary-container">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>
                        <p class="text-sm">Pengaduan Anda sedang ditangani oleh Tim Sarana Sekolah. Pantau halaman ini — status akan berubah begitu perbaikan selesai, dan foto dokumentasinya akan muncul di sini.</p>
                    </div>
                @endif

                @if ($complaint->status === 'rejected')
                    <div class="mt-6 flex items-start gap-3 rounded-xl bg-error-container p-4 text-on-error-container">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm8.943 3.375c-1.378-2.397-7.534-13.07-9.964-17.272C10.39 1.2 8.827 1.112 8.25 2.362c-1.022 2.025 3.018 5.17 3.018 5.17L3 17.25M21 17.25l-1.574 2.727a2.25 2.25 0 0 1-1.95 1.078H6.524a2.25 2.25 0 0 1-1.95-1.078L3 17.25" />
                        </svg>
                        <div class="text-sm">
                            <p class="font-medium">Pengaduan Anda ditolak.</p>

                            {{-- The why sits between the verdict and the way out:
                                 the reason is the premise of this banner, so it
                                 renders before the advice that acts on it. --}}
                            @if ($complaint->admin_notes)
                                <div class="mt-3 pt-3 border-t border-on-error-container/20">
                                    <h3 class="font-medium">{{ __('Alasan Penolakan') }}</h3>
                                    <p class="mt-1 whitespace-pre-line">{{ $complaint->admin_notes }}</p>
                                </div>
                            @endif

                            <p class="mt-3">{{ __('Bila masalahnya masih terjadi, Anda dapat mengajukan pengaduan baru.') }}</p>
                            <a href="{{ route('siswa.pengaduan.create') }}" class="inline-flex items-center gap-1 mt-2 font-medium text-on-error-container underline underline-offset-4">
                                {{ __('Buat pengaduan baru') }}
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @elseif ($complaint->admin_notes)
                    <div class="mt-6 rounded-xl bg-secondary-container text-on-secondary-container p-4">
                        <h3 class="font-medium text-on-secondary-container">{{ __('Catatan Admin') }}</h3>
                        <p class="mt-1.5 text-sm whitespace-pre-line">{{ $complaint->admin_notes }}</p>
                    </div>
                @endif

                @if ($complaint->status === 'resolved')
                    <div class="mt-6 flex items-start gap-3 rounded-xl bg-success-container p-4 text-on-success-container">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="text-sm">Pengaduan Anda sudah selesai ditangani. Terima kasih sudah melapor &mdash; sarana sekolah menjadi lebih baik karena Anda.</p>
                    </div>
                @endif

                @if ($complaint->status === 'resolved' && $complaint->resolved_image)
                    <x-photo-frame id="resolved-image"
                        :src="asset('storage/'.$complaint->resolved_image)"
                        :alt="__('Dokumentasi perbaikan')"
                        :title="__('Dokumentasi Perbaikan')"
                        :fallback="__('Dokumentasi perbaikan tidak dapat ditampilkan.')" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>