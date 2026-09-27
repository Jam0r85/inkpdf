<?php

declare(strict_types=1);

namespace InkPdf\Tests;

use InkPdf\InkPdf;
use PHPUnit\Framework\TestCase;

final class ImageSanitisingTest extends TestCase
{
    private function jpegDataUri(int $width, int $height): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is needed to build test images.');
        }

        $image = imagecreatetruecolor($width, $height);
        // Noise so the JPEG doesn't compress to almost nothing.
        for ($i = 0; $i < $width * $height / 4; $i++) {
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), random_int(0, 0xFFFFFF));
        }
        ob_start();
        imagejpeg($image, null, 95);

        return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
    }

    private function imageCount(string $pdf): int
    {
        return preg_match_all('#/Subtype\s*/Image#', $pdf);
    }

    public function test_a_jpeg_data_uri_image_is_embedded_with_its_data(): void
    {
        $src = $this->jpegDataUri(40, 30);

        $pdf = InkPdf::loadHtml('<p>Photo</p><img src="'.$src.'" width="120" height="90">')->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(1, $this->imageCount($pdf));
        // The real 40×30 photo, not mPDF's missing-image placeholder.
        $this->assertMatchesRegularExpression('#/Width\s+40\b#', $pdf);
        $this->assertMatchesRegularExpression('#/Height\s+30\b#', $pdf);
    }

    public function test_webp_and_remote_images_are_still_dropped(): void
    {
        $pdf = InkPdf::loadHtml(
            '<p>Text</p><img src="data:image/webp;base64,UklGRg=="><img src="https://example.com/a.jpg">'
        )->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(0, $this->imageCount($pdf));
    }

    public function test_large_photos_do_not_blank_the_document_at_the_default_backtrack_limit(): void
    {
        $previous = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '1000000');

        try {
            $images = '';
            for ($i = 0; $i < 3; $i++) {
                $images .= '<img alt="photo '.$i.'" src="'.$this->jpegDataUri(900, 700).'" width="300" height="230">';
            }
            $this->assertGreaterThan(1_000_000, strlen($images));

            $pdf = InkPdf::loadHtml('<h1>Brochure</h1>'.$images)->output();

            $this->assertStringStartsWith('%PDF', $pdf);
            $this->assertSame(3, preg_match_all('#/Width\s+900\b#', $pdf));
        } finally {
            ini_set('pcre.backtrack_limit', (string) $previous);
        }
    }
}
