@section('title', __('Buat Pengaduan'))
<x-app-layout>
    <x-slot name="header">
        {{-- Same column as the card below: at wide desktop the heading would
             otherwise sit on the full-width page rail, far left of the form
             it names (the detail page's header is the established pattern). --}}
        <div class="w-full max-w-3xl mx-auto flex items-center gap-3">
            <x-back-button :href="route('siswa.pengaduan.index')" :label="__('Kembali ke pengaduan saya')" />
            <h1 class="text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Buat Pengaduan') }}
            </h1>
        </div>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @php($hasRoomChoices = $roomGroups->flatten()->isNotEmpty())
            @if ($categories->isEmpty() || ! $hasRoomChoices)
                {{-- The form is an unusable dead end without master data: both
                     selects are placeholder-only and native `required` would
                     block submission with a bubble in the browser's locale.
                     Named the fixer instead of the fix — master data is
                     admin-only, so the notice points at the admin, and the
                     server-side condition (not Alpine) keeps it working
                     without JS. --}}
                <div class="mt-5 mb-5">
                    <div class="flex items-center gap-2.5 rounded-xl bg-warning-container text-on-warning-container px-4 py-3 text-sm font-medium" role="alert">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        Belum ada kategori atau ruang yang tersedia — form belum bisa dikirim. Silakan hubungi admin untuk menambahkan datanya terlebih dahulu.
                    </div>
                </div>
            @endif
            <section class="card p-6 text-on-surface">
                <form method="POST" action="{{ route('siswa.pengaduan.store') }}" enctype="multipart/form-data"
                    x-data="{ preview: null, submitting: false, fileLabel: 'Pilih foto bukti…' }" @submit="submitting = true">
                    @csrf

                    {{-- The evidence upload can ride a slow school connection, so
                         between "Kirim Pengaduan" and the response the button goes
                         dark and says what it is doing — the gap a 2 MB upload used
                         to fill with silence, and the click that could silently
                         create a duplicate ticket. Native, not fetch: if JS is off,
                         the form still posts; if validation fails, the redirect is
                         a fresh page load and the state resets itself. --}}
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="category_id" :value="__('Kategori')" />
                            {{-- The picker is rendered in-page (x-select-menu): the
                                 native select popup is the one thing a page cannot
                                 keep inside a laptop inspect's phone frame, so the
                                 menu is ordinary DOM that scrolls with the form. --}}
                            <x-select-menu id="category_id" name="category_id" class="mt-1.5"
                                :value="old('category_id')"
                                placeholder="Pilih kategori…"
                                nativeLabel="Kategori"
                                :options="$categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name])->all()" />
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="room_id" :value="__('Ruang')" />
                            {{-- 105 rooms made the flat alphabetical select a scroll --
                                 marathon: the numbered classrooms (what a student is
                                 closest to when something breaks) sit at the top of
                                 their floor's group now, and the grouped menu renders
                                 the same plan order (and native-label list) here. Empty
                                 groups are skipped so the dead-end guard above stays
                                 the only "no data" signal. The menu is searchable:
                                 typing "lab" or a room code filters the options
                                 instead of scrolling five floors. --}}
                            <x-select-menu id="room_id" name="room_id" class="mt-1.5"
                                searchable
                                searchPlaceholder="Cari ruang…"
                                searchEmpty="Tidak ada ruang yang cocok"
                                :value="old('room_id')"
                                placeholder="Pilih ruang…"
                                nativeLabel="Ruang"
                                :options="collect(\App\Models\Room::CREATE_FORM_GROUPS)
                                    ->filter(fn ($groupLabel) => $roomGroups->get($groupLabel)?->isNotEmpty())
                                    ->map(fn ($groupLabel) => [
                                        'label' => $groupLabel,
                                        'options' => $roomGroups->get($groupLabel)
                                            ->map(fn ($room) => ['value' => (string) $room->id, 'label' => $room->name.' ('.$room->code.')'])
                                            ->all(),
                                    ])
                                    ->all()" />
                            <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="title" :value="__('Judul Pengaduan')" />
                            {{-- Native `required`/`maxlength` mirror StoreComplaintRequest's
                                 rules — they catch the obvious slip in one keystroke,
                                 they never replace the server rules (every request is
                                 still re-validated there). The native bubble speaks
                                 the browser's locale; the Indonesian server messages
                                 below every field remain the copy that carries the
                                 "why". --}}
                            <x-text-input id="title" name="title" type="text" required maxlength="255" class="mt-1.5 w-full"
                                :value="old('title')" placeholder="Contoh: AC ruang kelas tidak menyala" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Deskripsi')" />
                            <textarea id="description" name="description" rows="5" required maxlength="5000"
                                class="field mt-1.5"
                                placeholder="Jelaskan kerusakan sarana dan kapan mulai terjadi…">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="evidence_image" :value="__('Foto Bukti')" />
                            {{-- The trigger is the app's own capsule (btn-secondary),
                                 not the browser's unstyleable "Choose File" — the
                                 one piece of English the form used to carry. The
                                 input stays in the DOM (sr-only) so the label's
                                 click opens the real picker, focusing it offers a
                                 visible ring via focus-within, and native
                                 `required` still gates the submit with its bubble.
                                 The label text flips from the invitation to the
                                 picked filename (Alpine-enhanced; without JS the
                                 invitation text still opens the picker). --}}
                            {{-- fileLabel/preview live on the form's scope: the
                                 preview block below reads them, and a nested
                                 x-data on this label left `preview` empty there
                                 (the block appeared with a broken image). --}}
                            <label class="file-field mt-1.5">
                                <input id="evidence_image" name="evidence_image" type="file" required
                                    accept="image/jpeg,image/png" class="sr-only"
                                    @change="const file = $event.target.files[0]; fileLabel = file?.name ?? 'Pilih foto bukti…'; preview = file ? URL.createObjectURL(file) : null" />
                                <svg class="h-4.5 w-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                </svg>
                                <span class="truncate" x-text="fileLabel">Pilih foto bukti…</span>
                            </label>
                            <p class="mt-2 text-xs text-on-surface-variant">
                                Wajib. JPG atau PNG, maksimal 2 MB.
                            </p>
                            <x-input-error :messages="$errors->get('evidence_image')" class="mt-2" />

                            <div x-show="preview" x-cloak class="mt-3">
                                <p class="text-sm text-on-surface-variant mb-2">Pratinjau:</p>
                                <img :src="preview" alt="Pratinjau bukti"
                                    class="max-h-64 rounded-xl border border-outline-variant object-cover" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button x-bind:disabled="submitting">
                                <span x-show="!submitting">{{ __('Kirim Pengaduan') }}</span>
                                <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2" aria-live="polite">
                                    <x-spinner class="h-4 w-4 animate-spin" />
                                    {{ __('Mengirim…') }}
                                </span>
                            </x-primary-button>
                            <a href="{{ route('siswa.pengaduan.index') }}" class="btn-outline">
                                {{ __('Batal') }}
                            </a>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>