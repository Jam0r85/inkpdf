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

For finance-style business documents, **mPDF wins**. Neither replaces Chromium for full Tailwind sites — InkPDF is intentionally document-first.

## Install

### Private GitHub

```bash
composer config repositories.inkpdf vcs https://github.com/Jam0r85/inkpdf.git
composer require jam0r85/inkpdf:^0.1
```

GitHub must be able to access the private repo (SSH key or `composer` GitHub token / `auth.json`).

### Local path (sibling package)

```text
/path/to/your-app
/path/to/inkpdf
```

In your app `composer.json`:

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
    // Inter is the default — no need to setDefaultFont unless you want something else
    ->setMeta(title: 'Invoice INV-1001', author: 'Acme Ltd')
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

### Inter (default)

InkPDF **bundles Inter** (Regular, Bold, Italic, Bold Italic) under the SIL Open Font License and uses it as the default typeface. No setup required:

```php
InkPdf::loadHtml($html)->output(); // Inter
```

In CSS:

```css
body { font-family: Inter, DejaVu Sans, sans-serif; }
h1, th, .font-bold { font-weight: bold; }
```

mPDF’s **DejaVu** remains available as a fallback (`->setDefaultFont('DejaVu Sans')`).

### Custom / brand fonts

```php
InkPdf::loadHtml($html)
    ->addFont('Brand', __DIR__.'/fonts/Brand-Regular.ttf')
    ->addFont('Brand', __DIR__.'/fonts/Brand-Bold.ttf', weight: 'bold')
    ->setDefaultFont('Brand');
```

Supported files: `.ttf`, `.otf`, `.ttc`.

## CSS: what we improve on top of mPDF

mPDF is not a browser — **no real flex/grid**. InkPDF makes documents look modern *within those limits*:

### 1. Document utility stylesheet (optional)

```php
InkPdf::loadHtml($html)
    ->withDocumentStyles()
    ->withBrand(['ink' => '0f172a', 'primary' => '1d4ed8'])
    ->output();
```

Gives you print-safe utilities with **Tailwind-compatible class names** (mPDF-safe subset):

| Category | Examples |
|----------|----------|
| Type | `text-xs`…`text-6xl`, `font-medium`/`semibold`/`bold`, `leading-*`, `tracking-*`, `uppercase` |
| Colour | Full scales: `text-slate-500`, `bg-blue-700`, `border-red-200` (+ semantic `text-muted`, `bg-success`) |
| Space | Full scale `p-*` / `px-*` / `py-*` / `pt|pr|pb|pl-*` and matching margins `m-*` / `mt-*`… (0–24) |
| Width | `w-full`, `w-1/2` (also `w-1-2`), `w-1/3`, `w-2/3`, `w-45`, `w-55`, fixed `w-4`… |
| Borders | `border`, `border-t/r/b/l`, `border-2`, `rounded`, `rounded-lg`, `rounded-full` |
| Display | `block`, `inline-block`, `hidden` (**not** flex/grid) |
| Tables | `table-lines`, `table-bordered`, `table-zebra`, `totals`, `num` |
| Layout | `cols` (2-column **table** layout), `stack`, `panel`, `badge-*`, `footer-note` |
| Page | `page-break`, `break-inside-avoid`, `keep-together` |

Two-column layouts use tables, not flex:

```html
<table class="cols">
  <tr>
    <td class="w-1/2 p-2">Left</td>
    <td class="w-1/2 p-2 text-right text-slate-500">Right</td>
  </tr>
</table>
```

**Not supported** (mPDF limits): real `flex` / `grid`, `gap`, modern shadow stacks, transforms, arbitrary values beyond a few widths, and most interactive states (`hover:`, `focus:`).

### 2. CSS normalizer (on by default)

Before render, InkPDF rewrites common modern CSS so mPDF accepts it:

- `var(--token)` / `:root` custom properties → expanded values  
- `rgb(15 23 42)` / `rgb(... / 0.5)` → classic `rgb()` (alpha blended on white)  
- simple `hsl(...)` / pragmatic `oklch(...)` → `rgb()`  
- strips flex/grid-only props (`gap`, `justify-content`, …)  
- maps `display: flex|grid` → `block` (layout still needs tables)

```php
->normalizeCss(true)   // default
->normalizeCss(false)  // raw CSS only
```

### 3. Extra stylesheets

```php
->addStylesheet('.total { font-size: 14pt; }')
->addStylesheetFile(resource_path('css/pdf-brand.css'))
```

### Supported vs avoid

**Works well**

- Block layout, paragraphs, headings  
- Tables (`colspan`, `rowspan`, borders, widths, `thead`)  
- Images (`src` path or data URI)  
- Font size/family/weight, colours, borders, padding, margins  
- Page breaks, `@page` / mPDF headers & footers  
- InkPDF utilities + normalized modern colour/vars  

**Avoid**

- Real flexbox / grid layouts  
- Full Tailwind builds (use `withDocumentStyles()` utilities instead)  
- Filters, transforms, animations, JS  

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
| `->withDocumentStyles()` | Inject print utility CSS |
| `->withBrand([...])` | Brand colours + enable document styles |
| `->addStylesheet($css)` / `->addStylesheetFile($path)` | Extra CSS |
| `->normalizeCss(bool)` | Modern CSS downlevel (default on) |
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

## Using in a Laravel app

Keep Blade templates and business data in your application. Depend on this package only for HTML → PDF rendering:

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
            ->withDocumentStyles()
            ->setMeta(title: "Invoice {$number}", author: 'Acme Ltd')
            ->output();
    }
}
```

## License

MIT
