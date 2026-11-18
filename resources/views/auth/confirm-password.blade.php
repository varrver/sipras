<x-guest-layout :title="__('Konfirmasi Kata Sandi')">
    <h1 class="text-2xl font-bold tracking-tight text-on-surface">{{ __('Konfirmasi Kata Sandi') }}</h1>

    <div class="mt-4 mb-4 text-sm text-on-surface-variant">
        {{ __('Ini area aman aplikasi. Harap konfirmasi kata sandi Anda sebelum melanjutkan.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Kata Sandi')" />

            <x-text-input id="password" class="mt-1.5 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-5">
            <x-primary-button>
                {{ __('Konfirmasi') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>