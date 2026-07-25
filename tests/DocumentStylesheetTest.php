<?php

declare(strict_types=1);

namespace InkPdf\Tests;

use InkPdf\Css\DocumentStylesheet;
use InkPdf\InkPdf;
use PHPUnit\Framework\TestCase;

final class DocumentStylesheetTest extends TestCase
{
    public function test_base_includes_core_tailwind_utilities(): void
    {
        $css = DocumentStylesheet::base();

        $expected = [
            // Spacing scale (incl. half-steps with escaped dots)
            '.p-4',
            '.p-0\\.5',
            '.mt-8',
            '.px-3',
            '.mb-0',
            '.-mt-2',
            // Typography
            '.text-xs',
            '.text-4xl',
            '.font-semibold',
            '.tracking-wide',
            '.leading-tight',
            // Colours
            '.text-slate-500',
            '.bg-blue-700',
            '.border-red-200',
            '.text-emerald-600',
            // Width fractions (slash + legacy)
            '.w-1\\/2',
            '.w-1-2',
            '.w-3\\/4',
            // Borders / radius
            '.border-l',
            '.rounded-xl',
            // Display
            '.inline-block',
            '.hidden',
            // Document patterns preserved
            '.table-lines th',
            '.badge-success',
            '.cols',
        ];

        foreach ($expected as $needle) {
            $this->assertStringContainsString($needle, $css, "Missing utility: {$needle}");
        }
    }

    public function test_renders_pdf_using_extended_utilities(): void
    {
        $html = <<<'HTML'
<html><body>
  <div class="p-4 mb-6 border border-slate-200 rounded-lg bg-slate-50">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Utility smoke test</h1>
    <p class="text-sm text-slate-500 mt-2">Muted body copy with spacing.</p>
    <table class="w-full mt-4 table-bordered">
      <tr>
        <td class="w-1/2 p-2 text-left text-emerald-700 font-semibold">Left half</td>
        <td class="w-1/2 p-2 text-right text-blue-700">Right half</td>
      </tr>
    </table>
    <span class="badge badge-primary mt-3">Primary</span>
  </div>
</body></html>
HTML;

        $pdf = InkPdf::loadHtml($html)
            ->withDocumentStyles()
            ->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(500, strlen($pdf));
    }

    public function test_brand_overrides_still_apply(): void
    {
        $css = DocumentStylesheet::brand(['ink' => '112233', 'primary' => '445566']);

        $this->assertStringContainsString('#112233', $css);
        $this->assertStringContainsString('#445566', $css);
    }
}
