<x-guest-layout :title="__('Ada yang tidak beres di sisi kami')">
    <x-error-card
        code="500"
        tone="error"
        :title="__('Ada yang tidak beres di sisi kami')"
        :message="__('Kesalahan tak terduga saat memproses permintaan Anda. Coba muat ulang sebentar lagi — kalau masih gagal, beri tahu admin sekolah.')"
    />
</x-guest-layout>
