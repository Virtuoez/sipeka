<?php
// Membuat ikon aplikasi SIPEKA (gedung kantor putih di atas hijau) ke public/icons/.
// Jalankan ulang kalau ingin mengubah warna:  php tools/buat-ikon.php

$hijau = [15, 107, 79];
$emas = [201, 162, 39];
$keluar = __DIR__.'/../public/icons';
@mkdir($keluar, 0777, true);

/**
 * @param  float  $isi  porsi kanvas yang dipakai gambar (maskable butuh margin aman ~20%)
 */
function gambar(int $s, bool $bulat, float $isi, array $hijau, array $emas): GdImage
{
    // Gambar di 4x ukuran lalu diperkecil supaya tepinya halus
    $k = 4;
    $S = $s * $k;
    $im = imagecreatetruecolor($S, $S);
    imagesavealpha($im, true);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    $bg = imagecolorallocate($im, ...$hijau);
    $putih = imagecolorallocate($im, 255, 255, 255);
    $aksen = imagecolorallocate($im, ...$emas);

    if ($bulat) {
        $r = (int) ($S * 0.22);
        imagefilledrectangle($im, $r, 0, $S - $r, $S, $bg);
        imagefilledrectangle($im, 0, $r, $S, $S - $r, $bg);
        foreach ([[$r, $r], [$S - $r, $r], [$r, $S - $r], [$S - $r, $S - $r]] as [$x, $y]) {
            imagefilledellipse($im, $x, $y, $r * 2, $r * 2, $bg);
        }
    } else {
        imagefilledrectangle($im, 0, 0, $S, $S, $bg);
    }

    // Area gambar di tengah
    $m = $S * (1 - $isi) / 2;
    $w = $S - 2 * $m;
    $x0 = $m;
    $y0 = $m;
    $u = fn ($v) => (int) round($v);

    // Atap segitiga
    imagefilledpolygon($im, [
        $u($x0 + $w * 0.50), $u($y0 + $w * 0.08),
        $u($x0 + $w * 0.06), $u($y0 + $w * 0.36),
        $u($x0 + $w * 0.94), $u($y0 + $w * 0.36),
    ], $putih);
    // Bendera kecil (aksen emas) di atas atap
    imagefilledrectangle($im, $u($x0 + $w * 0.495), $u($y0 - $w * 0.06), $u($x0 + $w * 0.515), $u($y0 + $w * 0.10), $putih);
    imagefilledpolygon($im, [
        $u($x0 + $w * 0.515), $u($y0 - $w * 0.06),
        $u($x0 + $w * 0.64), $u($y0 - $w * 0.02),
        $u($x0 + $w * 0.515), $u($y0 + $w * 0.02),
    ], $aksen);
    // Balok di bawah atap
    imagefilledrectangle($im, $u($x0 + $w * 0.12), $u($y0 + $w * 0.39), $u($x0 + $w * 0.88), $u($y0 + $w * 0.45), $putih);
    // 4 tiang
    foreach ([0.17, 0.37, 0.57, 0.77] as $p) {
        imagefilledrectangle($im, $u($x0 + $w * $p), $u($y0 + $w * 0.49), $u($x0 + $w * ($p + 0.06)), $u($y0 + $w * 0.78), $putih);
    }
    // Lantai bertingkat
    imagefilledrectangle($im, $u($x0 + $w * 0.10), $u($y0 + $w * 0.81), $u($x0 + $w * 0.90), $u($y0 + $w * 0.86), $putih);
    imagefilledrectangle($im, $u($x0 + $w * 0.04), $u($y0 + $w * 0.89), $u($x0 + $w * 0.96), $u($y0 + $w * 0.94), $aksen);

    $hasil = imagecreatetruecolor($s, $s);
    imagesavealpha($hasil, true);
    imagefill($hasil, 0, 0, imagecolorallocatealpha($hasil, 0, 0, 0, 127));
    imagecopyresampled($hasil, $im, 0, 0, 0, 0, $s, $s, $S, $S);

    return $hasil;
}

foreach ([
    'ikon-192.png' => [192, true, 0.62],
    'ikon-512.png' => [512, true, 0.62],
    'ikon-maskable-512.png' => [512, false, 0.50], // Android memotong tepi; isi di zona aman
    'apple-touch-icon.png' => [180, false, 0.60],
    'favicon-32.png' => [32, true, 0.74],
] as $nama => [$ukuran, $bulat, $isi]) {
    imagepng(gambar($ukuran, $bulat, $isi, $hijau, $emas), "$keluar/$nama");
    echo "dibuat: public/icons/$nama\n";
}
