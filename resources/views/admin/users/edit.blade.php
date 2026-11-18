@section('title', __('Edit Pengguna'))
<x-app-layout>
    <x-slot name="header">
        <div class="w-full max-w-4xl mx-auto flex items-center gap-3">
            <x-back-button :href="route('admin.master.users.index')" :label="__('Kembali ke Manajemen Pengguna')" />
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Edit Pengguna') }}
            </h1>
        </div>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="card p-6">
                <form action="{{ route('admin.master.users.update', $user) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="name" :value="__('Nama Lengkap')" />
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus class="field mt-1.5" />
                            @error('name')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="field mt-1.5" />
                            @error('email')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Password Baru')" />
                            <input type="password" id="password" name="password" autocomplete="new-password" class="field mt-1.5" />
                            <p class="mt-2 text-xs text-on-surface-variant">Kosongkan jika tidak ingin mengubah password.</p>
                            @error('password')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="field mt-1.5" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Peran (Role)')" />
                        {{-- In-page picker (x-select-menu) so the dropdown can never
                             escape its layout under laptop-inspect device mode. --}}
                        <div x-data="{ role: '{{ old('role', $user->role) }}' }">
                            <x-select-menu id="role" name="role" class="mt-1.5"
                                :value="old('role', $user->role)"
                                nativeLabel="Peran (Role)"
                                x-on:changed="role = $event.detail.value"
                                :options="collect(\App\Models\User::ROLES)
                                    ->map(fn ($role) => ['value' => $role, 'label' => ucfirst($role)])
                                    ->all()" />

                            {{-- The teknisi's specialty (D2): appears only when the
                                 picked role is teknisi; a posted value under any other
                                 role is force-nulled server-side. Shown server-side too
                                 (no x-cloak) so a no-JS visitor can still edit a
                                 teknisi — validation refuses a stray category elsewhere. --}}
                            <div x-show="role === 'teknisi'">
                                <x-input-label for="category_id" :value="__('Spesialisasi Kategori')" class="mt-4" />
                                <x-select-menu id="category_id" name="category_id" class="mt-1.5"
                                    :value="(string) old('category_id', $user->category_id ?? '')"
                                    nativeLabel="Spesialisasi Kategori"
                                    :options="\App\Models\Category::orderBy('name')->get()
                                        ->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name])
                                        ->all()" />
                                @error('category_id')
                                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        @if ($user->is(auth()->user()))
                            <p class="mt-2 text-xs text-on-surface-variant">Peran (role) akun sendiri tidak dapat diubah.</p>
                        @endif
                        @error('role')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>
                            {{ __('Perbarui') }}
                        </x-primary-button>
                        <a href="{{ route('admin.master.users.index') }}" class="btn-outline">
                            {{ __('Batal') }}
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>