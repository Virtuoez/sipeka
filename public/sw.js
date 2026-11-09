// Service worker SIPEKA: membuat website bisa dipasang sebagai aplikasi.
// Data (permohonan, NIK, dll) TIDAK disimpan di perangkat: selalu diambil langsung dari PC kelurahan.
// Yang disimpan hanya halaman "tidak tersambung" + ikon, untuk ditampilkan saat PC kelurahan mati / WiFi putus.
const CACHE = 'sipeka-v2';
const CADANGAN = ['/offline.html', '/icons/ikon-192.png'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(CADANGAN)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((kunci) => Promise.all(kunci.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;

    // Perpindahan halaman gagal (PC kelurahan mati / WiFi putus) → tampilkan halaman "tidak tersambung"
    if (e.request.mode === 'navigate') {
        e.respondWith(fetch(e.request).catch(() => caches.match('/offline.html')));
        return;
    }

    // Ikon di halaman "tidak tersambung" diambil dari simpanan kalau jaringan gagal
    const jalur = new URL(e.request.url).pathname;
    if (CADANGAN.includes(jalur)) {
        e.respondWith(fetch(e.request).catch(() => caches.match(jalur)));
    }
    // Permintaan lain dibiarkan apa adanya (tidak ada data yang disimpan di perangkat)
});
