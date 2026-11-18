<x-guest-layout :title="__('Sesi Anda berakhir')">
    <x-error-card
        code="419"
        tone="warning"
        :title="__('Sesi Anda berakhir')"
        :message="__('Halaman ini kedaluwarsa demi keamanan, jadi perubahan Anda belum tersimpan. Masuk lagi, lalu ulangi tindakan tadi.')"
    />
</x-guest-layout>
