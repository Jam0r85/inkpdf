# InkPDF

Document-focused **HTML → PDF** for PHP.

Built for business documents — invoices, payment advices, statements, letters — with solid **table layout**, **Unicode**, and **custom font embedding**. Not a browser: no Chromium, no JavaScript. Style with HTML tables and print-friendly CSS.

**Backend:** [mPDF](https://mpdf.github.io/) (swappable via `PdfRenderer`).

## Why mPDF (not dompdf)?

| | **mPDF** (InkPDF default) | **dompdf** |
|--|---------------------------|------------|
| Unicode / UTF-8 | Excellent (names, £/€, accents) | Historically weaker |
| Custom TTF/OTF fonts | Strong, well-trodden | Works, more fiddly |
| Tables (invoices) | Very good | Good |
| CSS scope | CSS-ish, document-oriented | CSS 2.1-ish |
| Headers / footers / page numbers | Mature | Basic |
| Flexbox / modern CSS | No | No |
| Weight / deps | Heavier | Lighter |
| Best for | Invoices, multi-language docs | Simple HTML receipts |

For PropDesk-style finance docs, **mPDF wins**. Neither replaces Chromium for full Tailwind sites — InkPDF is intentionally document-first.

## Install

### Private GitHub (PropDesk / apps)

```bash
composer config repositories.inkpdf vcs https://github.com/Jam0r85/inkpdf.git
composer require jam0r85/inkpdf:^0.1
```

GitHub must be able to access the private repo (SSH key or `composer` GitHub token / `auth.json`).

### Local path (WSL / monorepo-style)

If the package sits next to PropDesk:

```text
/home/james/sites/propdesk
/home/james/sites/inkpdf
```

In PropDesk `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../inkpdf",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "jam0r85/inkpdf": "@dev"
  }
}
```

Then:

```bash
composer update jam0r85/inkpdf
```

## Quick start

```php
use InkPdf\InkPdf;

$html = view('pdf.invoice', compact('invoice'))->render(); // Laravel
// or plain: $html = file_get_contents('invoice.html');

$pdf = InkPdf::loadHtml($html)
    ->setPaper('A4')
    ->setMargins(12)
    ->setDefaultFont('DejaVu Sans', 10)
    ->setMeta(title: 'Invoice INV-1001', author: 'PropDesk')
    ->addFont('Brand', resource_path('fonts/Brand-Regular.ttf'))
    ->addFont('Brand', resource_path('fonts/Brand-Bold.ttf'), weight: 'bold');

// Binary
$bytes = $pdf->output();

// Filesystem
$pdf->save(storage_path('app/invoices/INV-1001.pdf'));

// Classic PHP download / inline (exits)
// $pdf->download('INV-1001.pdf');
// $pdf->stream('INV-1001.pdf');
```

### Laravel response

```php
return response($pdf->output(), 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'inline; filename="INV-1001.pdf"',
]);
```

## Fonts

mPDF ships **DejaVu** by default (good Unicode coverage). Register company fonts:

```php
InkPdf::loadHtml($html)
    ->addFont('Inter', __DIR__.'/fonts/Inter-Regular.ttf')
    ->addFont('Inter', __DIR__.'/fonts/Inter-Bold.ttf', weight: 'bold')
    ->addFont('Inter', __DIR__.'/fonts/Inter-Italic.ttf', style: 'italic')
    ->setDefaultFont('Inter');
```

In CSS / HTML:

```html
<style>
  body { font-family: Inter, DejaVu Sans, sans-serif; font-size: 10pt; }
  h1, th, .bold { font-weight: bold; }
</style>
```

Supported files: `.ttf`, `.otf`, `.ttc`.

## Supported HTML/CSS (practical)

**Works well**

- Block layout, paragraphs, headings
- Tables (`colspan`, `rowspan`, borders, widths, `thead`)
- Images (`src` path or data URI)
- Inline styles + `<style>` blocks
- Font size/family/weight, colors, borders, padding, margins
- Page breaks: `page-break-before/after`, `pagebreak`
- `@page` / mPDF header-footer patterns

**Avoid / limited**

- Flexbox, Grid, floats for complex layout
- CSS variables, modern color functions (`oklch`)
- JavaScript
- Full Tailwind utility sheets (use tables + a small print stylesheet instead)

See `resources/templates/` for invoice and payment-advice examples.

## API

| Method | Purpose |
|--------|---------|
| `InkPdf::loadHtml($html)` | Start from HTML string |
| `InkPdf::loadFile($path)` | Start from HTML file |
| `->setPaper('A4'\|'A5'\|'Letter'\|'Legal', 'portrait'\|'landscape')` | Page size |
| `->setMargins(15)` or `->setMargins(['top'=>10, ...])` | mm |
| `->setDefaultFont($family, $sizePt?)` | Default typeface |
| `->addFont($family, $path, $style='normal', $weight='normal')` | Embed TTF/OTF |
| `->setMeta(title:, author:, subject:)` | PDF metadata |
| `->setTempDir($path)` | mPDF temp (writable) |
| `->output()` | `string` PDF bytes |
| `->save($path)` | Write file, return path |
| `->download($filename)` / `->stream($filename)` | HTTP helpers |

Swap the engine later by implementing `InkPdf\Contracts\PdfRenderer` and passing it into `InkPdf::loadHtml($html, $renderer)`.

## Examples

```bash
composer install
composer example:invoice
composer example:payment-advice
# PDFs land in examples/output/
```

## Tests

```bash
composer test
```

## PropDesk

Keep Blade/Twig templates and business data in PropDesk. Depend on this package only for HTML → PDF rendering:

```php
// app/Services/DocumentPdf.php
namespace App\Services;

use InkPdf\InkPdf;

final class DocumentPdf
{
    public function invoice(string $html, string $number): string
    {
        return InkPdf::loadHtml($html)
            ->setPaper('A4')
            ->setDefaultFont('DejaVu Sans')
            ->setMeta(title: "Invoice {$number}", author: 'PropDesk')
            ->output();
    }
}
```

## License

MIT
