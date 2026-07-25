<?php

declare(strict_types=1);

namespace InkPdf\Tests;

use InkPdf\Exceptions\InkPdfException;
use InkPdf\InkPdf;
use InkPdf\Orientation;
use InkPdf\PaperSize;
use PHPUnit\Framework\TestCase;

final class InkPdfTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = dirname(__DIR__) . '/tests/output';
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0775, true);
        }
    }

    public function test_renders_simple_html_to_pdf_bytes(): void
    {
        $html = '<html><body><h1>Hello InkPDF</h1><p>Total: £1,234.56</p></body></html>';

        $pdf = InkPdf::loadHtml($html)
            ->setPaper(PaperSize::A4)
            ->setDefaultFont('DejaVu Sans')
            ->setMeta(title: 'Test')
            ->output();

        $this->assertNotSame('', $pdf);
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_saves_invoice_with_document_styles(): void
    {
        $template = dirname(__DIR__) . '/resources/templates/invoice.html';
        $path = $this->outputDir . '/invoice.pdf';

        $saved = InkPdf::loadFile($template)
            ->setPaper('A4', 'portrait')
            ->setMargins(['top' => 12, 'right' => 12, 'bottom' => 12, 'left' => 12])
            ->setDefaultFont('DejaVu Sans', 10)
            ->withDocumentStyles()
            ->withBrand(['ink' => '0f172a'])
            ->save($path);

        $this->assertSame($path, $saved);
        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path));
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($path));
    }

    public function test_saves_payment_advice_template(): void
    {
        $template = dirname(__DIR__) . '/resources/templates/payment-advice.html';
        $path = $this->outputDir . '/payment-advice.pdf';

        InkPdf::loadFile($template)
            ->setPaper(PaperSize::A4, Orientation::Portrait)
            ->withDocumentStyles()
            ->save($path);

        $this->assertFileExists($path);
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($path));
    }

    public function test_add_stylesheet_and_normalize_toggle(): void
    {
        $html = <<<'HTML'
<html><head><style>
:root { --c: #b91c1c; }
.x { color: var(--c); display: flex; }
</style></head><body><p class="x">Hi</p></body></html>
HTML;

        $pdf = InkPdf::loadHtml($html)
            ->addStylesheet('.x { font-weight: bold; }')
            ->normalizeCss(true)
            ->output();

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_rejects_empty_html(): void
    {
        $this->expectException(InkPdfException::class);

        InkPdf::make()->output();
    }

    public function test_rejects_missing_font_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        InkPdf::loadHtml('<p>x</p>')
            ->addFont('Missing', '/no/such/font.ttf')
            ->output();
    }
}
