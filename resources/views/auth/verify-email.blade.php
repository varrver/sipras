<x-guest-layout :title="__('Verifikasi Email')">
    <h1 class="text-2xl font-bold tracking-tight text-on-surface">{{ __('Verifikasi Email') }}</h1>

    <div class="mt-4 mb-4 text-sm text-on-surface-variant">
        {{ __('Terima kasih telah mendaftar! Sebelum memulai, verifikasi email Anda dengan mengklik tautan yang kami kirimkan. Jika Anda tidak menerima email, kami akan mengirim ulang.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-primary">
            {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Kirim Ulang Email Verifikasi') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm font-medium text-primary underline underline-offset-4 hover:text-primary/80">
                {{ __('Keluar') }}
            </button>
        </form>
    </div>
</x-guest-layout>