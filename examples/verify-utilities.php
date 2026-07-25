<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use InkPdf\Css\DocumentStylesheet;
use InkPdf\Css\HtmlStyler;
use InkPdf\InkPdf;

$css = DocumentStylesheet::base();

$required = [
    '.text-slate-500',
    '.bg-blue-700',
    '.p-4',
    '.mt-8',
    '.w-1\\/2',
    '.w-1-2',
    '.font-semibold',
    '.border-l',
    '.rounded-lg',
    '.hidden',
    '.text-emerald-700',
    '.px-3',
    '.mb-0',
];

echo "CSS bytes: " . strlen($css) . PHP_EOL;
foreach ($required as $needle) {
    $ok = str_contains($css, $needle);
    echo ($ok ? 'OK  ' : 'MISS') . " {$needle}" . PHP_EOL;
    if (! $ok) {
        exit(1);
    }
}

$html = <<<'HTML'
<html><body>
<div class="p-4 mb-6 border border-slate-200 rounded-lg bg-slate-50">
  <h1 class="text-2xl font-bold text-slate-900">Title</h1>
  <p class="text-sm text-slate-500 mt-2">Muted body</p>
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

$styled = (new HtmlStyler())->apply($html, [DocumentStylesheet::base()], true);
if (! str_contains($styled, 'text-emerald-700')) {
    fwrite(STDERR, "HtmlStyler did not inject stylesheet\n");
    exit(1);
}
if (! str_contains($styled, 'w-1\\/2') && ! str_contains($styled, '.w-1\\/2')) {
    // after inject, CSS should still have escaped selector
    if (! str_contains($styled, '.w-1\\/2')) {
        fwrite(STDERR, "Slash width selector missing after inject\n");
        exit(1);
    }
}

$path = dirname(__DIR__) . '/tests/output/utility-smoke.pdf';
InkPdf::loadHtml($html)->withDocumentStyles()->save($path);

$bytes = file_get_contents($path);
if ($bytes === false || ! str_starts_with($bytes, '%PDF') || strlen($bytes) < 1000) {
    fwrite(STDERR, "PDF render failed\n");
    exit(1);
}

echo "Injected styles: yes\n";
echo "PDF: {$path} (" . strlen($bytes) . " bytes)\n";
echo "All utility checks passed.\n";
