<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageWatermarkService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::usingDriver(Driver::class);
    }

    public function processCheckinPhoto(
        UploadedFile $file,
        array $watermarkLines,
        string $folder = 'visits',
        int $targetWidth = 800,
        int $maxSizeKB = 150
    ): string {
        // 1. Decode foto asli
        $image = $this->manager->decode(file_get_contents($file->getRealPath()));

        // 2. Resize ke lebar TETAP, supaya semua foto konsisten ukurannya
        $image->scale(width: $targetWidth);

        // 3. Compress dulu (tanpa watermark) sampai di bawah target size
        $compressedBytes = $this->compressToMaxSize($image, $maxSizeKB);

        // 4. Decode ulang dari hasil kompresi
        $image = $this->manager->decode((string) $compressedBytes);

        // 5. Gambar watermark di atas gambar yang sudah di-resize & dikompres
        $this->drawWatermarkBox($image, $watermarkLines);

        // 6. Encode final
        $finalEncoded = $image->encodeUsingFormat(Format::JPEG, quality: 85);

        // 7. Simpan ke storage
        $filename = $folder.'/'.uniqid('checkin_').'.jpg';
        $fullPath = storage_path('app/public/'.$filename);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, (string) $finalEncoded);

        return $filename;
    }

    protected function drawWatermarkBox($image, array $lines): void
    {
        $paddingX = 16;
        $paddingY = 14;
        $fontSize = 30;
        $lineHeight = 36;   // harus LEBIH BESAR dari fontSize, supaya tidak tabrakan
        $marginBottom = 8;
        $marginLeft = 8;
        // PENTING: $font->size() cuma berpengaruh kalau font TTF ini ada.
        // Tanpa TTF, GD driver fallback ke built-in bitmap font yang ukurannya
        // FIXED dan mengabaikan $font->size() sama sekali — itu sebabnya ganti
        // fontSize kelihatan "tidak ngaruh". File saat ini cuma placeholder
        // (disalin dari font sistem lokal) — ganti dengan font TTF berlisensi
        // jelas (mis. Roboto dari Google Fonts, lisensi OFL) sebelum deploy ke production.
        $fontPath = public_path('fonts/watermark-font.ttf');

        $boxWidth = (int) ($image->width() * 0.78);

        $textAreaWidth = $boxWidth - ($paddingX * 2);

        $avgCharWidth = $fontSize * 0.55;
        $maxCharsPerLine = max(10, (int) floor($textAreaWidth / $avgCharWidth));

        $wrappedLines = [];
        foreach ($lines as $line) {
            $wrapped = wordwrap($line, $maxCharsPerLine, "\n", true);
            foreach (explode("\n", $wrapped) as $subLine) {
                $wrappedLines[] = $subLine;
            }
        }

        $boxHeight = ($lineHeight * count($wrappedLines)) + ($paddingY * 2);

        $imgHeight = $image->height();
        $boxTop = $imgHeight - $boxHeight - $marginBottom;
        $boxLeft = $marginLeft;

        if ($boxTop < 0) {
            $boxTop = 0;
        }

        $image->drawRectangle(function ($rectangle) use ($boxLeft, $boxTop, $boxWidth, $boxHeight) {
            $rectangle->at($boxLeft, $boxTop);
            $rectangle->size($boxWidth, $boxHeight);
            $rectangle->background('rgba(0, 0, 0, 0.55)');
        });

        foreach ($wrappedLines as $i => $line) {
            $y = $boxTop + $paddingY + ($i * $lineHeight);

            $image->text($line, $boxLeft + $paddingX, $y, function ($font) use ($fontSize, $fontPath) {
                if (file_exists($fontPath)) {
                    $font->filename($fontPath);
                }
                $font->size($fontSize);
                $font->color('#ffffff');
                $font->align('left', 'top');
            });
        }
    }

    protected function compressToMaxSize($image, int $maxSizeKB)
    {
        $quality = 85;
        $minQuality = 30;

        do {
            $encoded = $image->encodeUsingFormat(Format::JPEG, quality: $quality);
            $sizeKB = strlen((string) $encoded) / 1024;

            if ($sizeKB <= $maxSizeKB || $quality <= $minQuality) {
                break;
            }

            $quality -= 5;
        } while (true);

        return $encoded;
    }
}
