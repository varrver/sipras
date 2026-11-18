<x-guest-layout :title="__('Halaman tidak ditemukan')">
    <x-error-card
        code="404"
        tone="neutral"
        :title="__('Halaman tidak ditemukan')"
        :message="__('Alamat ini tidak ada atau sudah dipindahkan. Periksa kembali tautannya, atau kembali ke dashboard.')"
    />
</x-guest-layout>
