{{-- Supaya website bisa dipasang sebagai aplikasi di PC & tablet (PWA) --}}
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#0a4d39">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="SIPEKA">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script>
    // Service worker hanya bisa aktif di localhost atau alamat yang diizinkan (lihat README bagian "Pasang sebagai aplikasi")
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
    // Tombol "Pasang Aplikasi": muncul kalau browser mengizinkan dan aplikasi belum terpasang
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.ajakanPasang = e;
        document.querySelectorAll('[data-pasang-aplikasi]').forEach((b) => b.classList.remove('d-none'));
    });
    window.addEventListener('appinstalled', () => {
        document.querySelectorAll('[data-pasang-aplikasi]').forEach((b) => b.classList.add('d-none'));
    });
    function pasangAplikasi() {
        if (window.ajakanPasang) { window.ajakanPasang.prompt(); window.ajakanPasang = null; }
    }
</script>
