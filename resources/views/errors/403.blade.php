<x-guest-layout :title="__('Tidak punya akses')">
    <x-error-card
        code="403"
        tone="warning"
        :title="__('Tidak punya akses')"
        :message="__('Halaman ini hanya untuk peran tertentu. Kalau menurut Anda ini keliru, hubungi admin sekolah.')"
    />
</x-guest-layout>
