@section('title', __('Edit Ruangan'))
<x-app-layout>
    <x-slot name="header">
        <div class="w-full max-w-4xl mx-auto flex items-center gap-3">
            <x-back-button :href="route('admin.master.rooms.index')" :label="__('Kembali ke Manajemen Ruangan')" />
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Edit Ruangan') }}
            </h1>
        </div>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="card p-6">
                <form action="{{ route('admin.master.rooms.update', $room) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" :value="__('Nama Ruangan')" />
                        <input type="text" id="name" name="name" value="{{ old('name', $room->name) }}" required class="field mt-1.5" />
                        @error('name')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-input-label for="code" :value="__('Kode Ruangan')" />
                        <input type="text" id="code" name="code" value="{{ old('code', $room->code) }}" required class="field mt-1.5" />
                        @error('code')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>
                            {{ __('Perbarui') }}
                        </x-primary-button>
                        <a href="{{ route('admin.master.rooms.index') }}" class="btn-outline">
                            {{ __('Batal') }}
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>