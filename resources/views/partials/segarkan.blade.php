{{--
    Perbarui bagian halaman bertanda [data-segarkan] tanpa memuat ulang seluruh halaman
    (tidak berkedip, tidak mengunduh ulang CSS/JS). Pemakaian: @include('partials.segarkan', ['detik' => 10])
--}}
<script>
    (function () {
        let sibuk = false;
        async function segarkan() {
            if (sibuk || document.visibilityState !== 'visible') return;
            const jeda = window.jangkanSegarkan;
            if (jeda && jeda()) return; // halaman boleh menunda (mis. petugas sedang mengetik)
            sibuk = true;
            try {
                const r = await fetch(location.href, { headers: { Accept: 'text/html' }, cache: 'no-store' });
                if (r.redirected || !r.ok) { location.reload(); return; } // sesi habis / pindah halaman
                const baru = new DOMParser().parseFromString(await r.text(), 'text/html');
                document.querySelectorAll('[data-segarkan]').forEach((lama) => {
                    const pengganti = baru.querySelector('[data-segarkan="' + lama.dataset.segarkan + '"]');
                    if (pengganti && pengganti.innerHTML !== lama.innerHTML) lama.innerHTML = pengganti.innerHTML;
                });
            } catch (e) {
                // PC kelurahan sedang tidak terjangkau: coba lagi pada putaran berikutnya
            } finally {
                sibuk = false;
            }
        }
        setInterval(segarkan, {{ ($detik ?? 10) * 1000 }});
    })();
</script>
