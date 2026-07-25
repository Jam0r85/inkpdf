<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use InkPdf\InkPdf;

// Minimal invoice-like HTML matching propdesk invoice-inkpdf structure
$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
body { font-family: Inter, DejaVu Sans, sans-serif; font-size: 10pt; color: #32373c; margin: 0; padding: 0; }
table { border-collapse: collapse; }
.w-full { width: 100%; }
.brand-name { font-size: 16pt; font-weight: bold; color: #1a4d5c; margin: 0 0 4pt 0; }
.muted { color: #666666; font-size: 9pt; line-height: 1.4; }
.doc-title { font-size: 14pt; font-weight: bold; text-align: right; margin: 0 0 4pt 0; }
.meta-line { font-size: 9pt; color: #555555; text-align: right; margin: 1pt 0; }
.rule { border-bottom: 2pt solid #1a4d5c; margin: 8pt 0 14pt 0; height: 0; }
.label { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #1a4d5c; margin: 0 0 4pt 0; }
table.lines { width: 100%; margin: 16pt 0 10pt 0; }
table.lines th { background-color: #1a4d5c; color: #ffffff; font-size: 9pt; text-align: left; padding: 7pt 6pt; }
table.lines th.num, table.lines td.num { text-align: right; }
table.lines td { font-size: 9.5pt; padding: 7pt 6pt; border-bottom: 0.5pt solid #e5e5e5; }
table.totals { width: 45%; margin-left: auto; margin-top: 8pt; }
table.totals td { font-size: 9.5pt; padding: 3pt 4pt; }
table.totals td.num { text-align: right; }
table.totals tr.grand td { font-weight: bold; font-size: 11pt; color: #1a4d5c; border-top: 1.5pt solid #1a4d5c; padding-top: 6pt; }
.notes { margin-top: 16pt; padding: 8pt 10pt; background-color: #f5f5f5; font-size: 9pt; }
.footer { margin-top: 22pt; padding-top: 8pt; border-top: 0.5pt solid #ddd; font-size: 8pt; color: #777; text-align: center; }
</style>
</head>
<body>
<table class="w-full" cellpadding="0" cellspacing="0">
<tr>
<td width="55%" valign="top">
<div class="brand-name">Acme Property Management</div>
<div class="muted">12 High Street, Birmingham<br>accounts@example.com</div>
</td>
<td width="45%" valign="top" align="right">
<div class="doc-title">INVOICE</div>
<div class="meta-line"><strong>INV-1001</strong></div>
<div class="meta-line">Issue date: 25 Jul 2026</div>
<div class="meta-line">Due date: 8 Aug 2026</div>
<div class="meta-line">Status: Paid</div>
</td>
</tr>
</table>
<div class="rule"></div>
<table class="w-full">
<tr>
<td width="50%" valign="top">
<div class="label">Bill to</div>
<strong>Alex Tenant</strong><br>Flat 2, 14 Oak Road
</td>
<td width="50%" valign="top">
<div class="label">Property</div>
<strong>14 Oak Road</strong>
</td>
</tr>
</table>
<table class="lines">
<thead><tr><th>Description</th><th class="num">Net</th><th class="num">VAT</th><th class="num">Amount</th></tr></thead>
<tbody>
<tr><td>Management Fee at 10%</td><td class="num">£100.00</td><td class="num">£20.00 (20%)</td><td class="num">£120.00</td></tr>
</tbody>
</table>
<table class="totals">
<tr><td>Subtotal</td><td class="num">£100.00</td></tr>
<tr><td>VAT</td><td class="num">£20.00</td></tr>
<tr class="grand"><td>Total</td><td class="num">£120.00</td></tr>
</table>
<div class="notes"><strong>Payment details</strong><br>Account no: 12345678 · Sort code: 20-00-00</div>
<div class="footer">Acme Ltd · Company No: 12345678</div>
</body>
</html>
HTML;

$pdf = InkPdf::loadHtml($html)
    ->setPaper('A4')
    ->setMargins(12)
    ->setDefaultFont('Inter', 10)
    ->normalizeCss(false)
    ->output();

$pages = preg_match_all('/\/Type\s*\/Page\b/', $pdf);
$out = dirname(__DIR__) . '/examples/output/debug-pagecount.pdf';
file_put_contents($out, $pdf);

echo "pages={$pages}\n";
echo "bytes=".strlen($pdf)."\n";
echo "wrote={$out}\n";
