<?php

declare(strict_types=1);

namespace InkPdf\Tests;

use InkPdf\Fonts\BundledFonts;
use InkPdf\InkPdf;
use PHPUnit\Framework\TestCase;

final class BundledFontsTest extends TestCase
{
    public function test_inter_is_bundled(): void
    {
        $this->assertTrue(BundledFonts::interAvailable());
        $faces = BundledFonts::inter();
        $this->assertNotEmpty($faces);
        $this->assertSame('Inter', $faces[0]->family);
    }

    public function test_default_render_uses_inter_without_explicit_font(): void
    {
        $pdf = InkPdf::loadHtml('<html><body><p>Inter default £1,000</p></body></html>')
            ->withDocumentStyles()
            ->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}
