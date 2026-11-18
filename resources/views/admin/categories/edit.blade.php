@section('title', __('Edit Kategori'))
<x-app-layout>
    <x-slot name="header">
        <div class="w-full max-w-4xl mx-auto flex items-center gap-3">
            <x-back-button :href="route('admin.master.categories.index')" :label="__('Kembali ke Manajemen Kategori')" />
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-on-surface">
                {{ __('Edit Kategori') }}
            </h1>
        </div>
    </x-slot>

    <div class="pb-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <section class="card p-6">
                <form action="{{ route('admin.master.categories.update', $category) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" :value="__('Nama Kategori')" />
                        <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required class="field mt-1.5" />
                        @error('name')
                            <p class="mt-2 text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>
                            {{ __('Perbarui') }}
                        </x-primary-button>
                        <a href="{{ route('admin.master.categories.index') }}" class="btn-outline">
                            {{ __('Batal') }}
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>