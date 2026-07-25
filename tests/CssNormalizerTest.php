<?php

declare(strict_types=1);

namespace InkPdf\Tests;

use InkPdf\Css\CssNormalizer;
use PHPUnit\Framework\TestCase;

final class CssNormalizerTest extends TestCase
{
    public function test_expands_css_variables(): void
    {
        $html = <<<'HTML'
<html><head><style>
:root { --ink: #0f172a; --pad: 12px; }
.box { color: var(--ink); padding: var(--pad); }
.fallback { color: var(--missing, #111827); }
</style></head><body></body></html>
HTML;

        $out = (new CssNormalizer())->normalizeHtml($html);

        $this->assertStringContainsString('color: #0f172a', $out);
        $this->assertStringContainsString('padding: 12px', $out);
        $this->assertStringContainsString('color: #111827', $out);
        $this->assertStringNotContainsString('var(--ink)', $out);
    }

    public function test_normalizes_modern_rgb_and_alpha(): void
    {
        $css = 'color: rgb(15 23 42); background: rgb(0 0 0 / 0.5);';
        $out = (new CssNormalizer())->normalizeCss($css);

        $this->assertStringContainsString('rgb(15, 23, 42)', $out);
        // 50% black on white ≈ 128
        $this->assertMatchesRegularExpression('/rgb\(1[0-9]{2}, 1[0-9]{2}, 1[0-9]{2}\)/', $out);
    }

    public function test_maps_oklch_greys(): void
    {
        $css = 'color: oklch(0.2 0 0);';
        $out = (new CssNormalizer())->normalizeCss($css);

        $this->assertMatchesRegularExpression('/rgb\(\d+, \d+, \d+\)/', $out);
        $this->assertStringNotContainsString('oklch', $out);
    }

    public function test_drops_flex_and_grid_declarations(): void
    {
        $css = '.x { display: flex; gap: 12px; justify-content: space-between; color: red; }';
        $out = (new CssNormalizer())->normalizeCss($css);

        $this->assertStringContainsString('display: block', $out);
        $this->assertStringContainsString('color: red', $out);
        $this->assertStringNotContainsString('gap:', $out);
        $this->assertStringNotContainsString('justify-content', $out);
    }
}
