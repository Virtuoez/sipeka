<?php
// Membuat ikon aplikasi & logo website SIPEKA dari logo Kabupaten Banjar (resources/img/logo-kabupaten-banjar-hd.png)
// ke public/icons/. Jalankan ulang kalau logonya diganti:  php tools/buat-ikon.php

$sumber = imagecreatefrompng(__DIR__.'/../resources/img/logo-kabupaten-banjar-hd.png');
$keluar = __DIR__.'/../public/icons';
@mkdir($keluar, 0777, true);

/**
 * Logo di tengah kanvas persegi.
 *
 * @param  float  $isi  tinggi logo dibanding kanvas (maskable butuh margin aman ~20%)
 * @param  bool  $latarPutih  true = latar putih penuh (ikon Android/iOS), false = transparan
 */
function ikon(GdImage $logo, int $s, float $isi, bool $latarPutih): GdImage
{
    $im = imagecreatetruecolor($s, $s);
    imagesavealpha($im, true);
    imagealphablending($im, false);
    imagefill($im, 0, 0, $latarPutih ? imagecolorallocate($im, 255, 255, 255) : imagecolorallocatealpha($im, 0, 0, 0, 127));
    imagealphablending($im, true);

    $lw = imagesx($logo);
    $lh = imagesy($logo);
    $h = (int) round($s * $isi);
    $w = (int) round($h * $lw / $lh);
    imagecopyresampled($im, $logo, intdiv($s - $w, 2), intdiv($s - $h, 2), 0, 0, $w, $h, $lw, $lh);

    return $im;
}

foreach ([
    'ikon-192.png' => [192, 0.92, false],
    'ikon-512.png' => [512, 0.92, false],
    'ikon-maskable-512.png' => [512, 0.62, true], // Android memotong tepi; logo di zona aman
    'apple-touch-icon.png' => [180, 0.82, true],
    'favicon-32.png' => [32, 1.0, false],
] as $nama => [$ukuran, $isi, $putih]) {
    imagepng(ikon($sumber, $ukuran, $isi, $putih), "$keluar/$nama");
    echo "dibuat: public/icons/$nama\n";
}

// Logo untuk sidebar & halaman masuk (tinggi 160 px, latar transparan)
$h = 160;
$w = (int) round($h * imagesx($sumber) / imagesy($sumber));
$logo = imagecreatetruecolor($w, $h);
imagesavealpha($logo, true);
imagealphablending($logo, false);
imagefill($logo, 0, 0, imagecolorallocatealpha($logo, 0, 0, 0, 127));
imagealphablending($logo, true);
imagecopyresampled($logo, $sumber, 0, 0, 0, 0, $w, $h, imagesx($sumber), imagesy($sumber));
imagepng($logo, "$keluar/logo-banjar.png");
echo "dibuat: public/icons/logo-banjar.png\n";
