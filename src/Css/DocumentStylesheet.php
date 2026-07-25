<?php

declare(strict_types=1);

namespace InkPdf\Css;

/**
 * Print-safe CSS designed around what mPDF actually renders well.
 *
 * Tables + box model + typography — not flex/grid.
 * Use these utilities in templates, or inject automatically via InkPdf::withDocumentStyles().
 */
final class DocumentStylesheet
{
    public static function base(): string
    {
        return <<<'CSS'
/* InkPDF document base — mPDF-friendly */
* { box-sizing: border-box; }

body {
    font-family: Inter, DejaVu Sans, sans-serif;
    font-size: 11pt;
    line-height: 1.45;
    color: #111827;
    margin: 0;
    padding: 0;
}

p { margin: 0 0 8px 0; }
h1, h2, h3, h4 {
    margin: 0 0 8px 0;
    font-weight: bold;
    color: #0f172a;
    line-height: 1.25;
}
h1 { font-size: 20pt; }
h2 { font-size: 14pt; }
h3 { font-size: 12pt; }
h4 { font-size: 10.5pt; }

a { color: #1d4ed8; text-decoration: none; }

table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
}
th, td {
    vertical-align: top;
    text-align: left;
}

img { max-width: 100%; border: 0; }

hr {
    border: 0;
    border-top: 1px solid #e5e7eb;
    margin: 16px 0;
}

/* ---- Typography ---- */
.text-xs   { font-size: 8pt; }
.text-sm   { font-size: 9pt; }
.text-base { font-size: 11pt; }
.text-md   { font-size: 12pt; }
.text-lg   { font-size: 13pt; }
.text-xl   { font-size: 16pt; }
.text-2xl  { font-size: 20pt; }
.text-3xl  { font-size: 24pt; }

.font-normal { font-weight: normal; }
.font-bold   { font-weight: bold; }
.italic      { font-style: italic; }
.uppercase   { text-transform: uppercase; letter-spacing: 0.04em; }
.underline   { text-decoration: underline; }
.line-through { text-decoration: line-through; }

.leading-tight  { line-height: 1.2; }
.leading-normal { line-height: 1.45; }
.leading-loose  { line-height: 1.7; }

/* ---- Alignment ---- */
.text-left    { text-align: left; }
.text-center  { text-align: center; }
.text-right   { text-align: right; }
.text-justify { text-align: justify; }
.align-top    { vertical-align: top; }
.align-middle { vertical-align: middle; }
.align-bottom { vertical-align: bottom; }

/* ---- Colours (text) ---- */
.text-black   { color: #000000; }
.text-ink     { color: #0f172a; }
.text-body    { color: #111827; }
.text-muted   { color: #6b7280; }
.text-faint   { color: #9ca3af; }
.text-white   { color: #ffffff; }
.text-primary { color: #1d4ed8; }
.text-success { color: #047857; }
.text-warning { color: #b45309; }
.text-danger  { color: #b91c1c; }

/* ---- Backgrounds ---- */
.bg-white   { background-color: #ffffff; }
.bg-slate   { background-color: #f8fafc; }
.bg-muted   { background-color: #f3f4f6; }
.bg-ink     { background-color: #0f172a; }
.bg-primary { background-color: #1d4ed8; }
.bg-success { background-color: #ecfdf5; }
.bg-warning { background-color: #fffbeb; }
.bg-danger  { background-color: #fef2f2; }

/* ---- Borders ---- */
.border       { border: 1px solid #e5e7eb; }
.border-0     { border: 0; }
.border-t     { border-top: 1px solid #e5e7eb; }
.border-b     { border-bottom: 1px solid #e5e7eb; }
.border-ink   { border-color: #0f172a; }
.border-2     { border-width: 2px; border-style: solid; border-color: #e5e7eb; }
.border-t-2   { border-top: 2px solid #0f172a; }
.border-b-2   { border-bottom: 2px solid #0f172a; }
.rounded      { border-radius: 4px; }
.rounded-lg   { border-radius: 8px; }

/* ---- Spacing (padding) ---- */
.p-0  { padding: 0; }
.p-1  { padding: 4px; }
.p-2  { padding: 8px; }
.p-3  { padding: 12px; }
.p-4  { padding: 16px; }
.p-5  { padding: 20px; }
.p-6  { padding: 24px; }

.px-1 { padding-left: 4px; padding-right: 4px; }
.px-2 { padding-left: 8px; padding-right: 8px; }
.px-3 { padding-left: 12px; padding-right: 12px; }
.px-4 { padding-left: 16px; padding-right: 16px; }
.py-1 { padding-top: 4px; padding-bottom: 4px; }
.py-2 { padding-top: 8px; padding-bottom: 8px; }
.py-3 { padding-top: 12px; padding-bottom: 12px; }
.py-4 { padding-top: 16px; padding-bottom: 16px; }

.pt-0 { padding-top: 0; }
.pt-2 { padding-top: 8px; }
.pt-3 { padding-top: 12px; }
.pt-4 { padding-top: 16px; }
.pb-2 { padding-bottom: 8px; }
.pb-3 { padding-bottom: 12px; }
.pb-4 { padding-bottom: 16px; }
.pl-2 { padding-left: 8px; }
.pr-2 { padding-right: 8px; }

/* ---- Spacing (margin) ---- */
.m-0  { margin: 0; }
.m-2  { margin: 8px; }
.m-3  { margin: 12px; }
.m-4  { margin: 16px; }
.mt-0 { margin-top: 0; }
.mt-1 { margin-top: 4px; }
.mt-2 { margin-top: 8px; }
.mt-3 { margin-top: 12px; }
.mt-4 { margin-top: 16px; }
.mt-5 { margin-top: 20px; }
.mt-6 { margin-top: 24px; }
.mt-8 { margin-top: 32px; }
.mb-0 { margin-bottom: 0; }
.mb-1 { margin-bottom: 4px; }
.mb-2 { margin-bottom: 8px; }
.mb-3 { margin-bottom: 12px; }
.mb-4 { margin-bottom: 16px; }
.mb-6 { margin-bottom: 24px; }
.ml-auto { margin-left: auto; }
.mr-auto { margin-right: auto; }
.mx-auto { margin-left: auto; margin-right: auto; }

/* ---- Width helpers (tables / blocks) ---- */
.w-full   { width: 100%; }
.w-auto   { width: auto; }
.w-1-2    { width: 50%; }
.w-1-3    { width: 33.33%; }
.w-2-3    { width: 66.66%; }
.w-1-4    { width: 25%; }
.w-3-4    { width: 75%; }
.w-2-5    { width: 40%; }
.w-3-5    { width: 60%; }
.w-45     { width: 45%; }
.w-55     { width: 55%; }

/* ---- Tables: document patterns ---- */
.table-plain th,
.table-plain td {
    padding: 2px 0;
    border: 0;
}

.table-lines th {
    background-color: #0f172a;
    color: #ffffff;
    font-size: 9pt;
    font-weight: bold;
    padding: 8px 6px;
    border: 0;
}
.table-lines td {
    padding: 8px 6px;
    border-bottom: 1px solid #e5e7eb;
}
.table-lines .num,
.num {
    text-align: right;
    white-space: nowrap;
}

.table-zebra tbody tr:nth-child(even) td {
    background-color: #f8fafc;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #e5e7eb;
    padding: 8px 6px;
}

.table-compact th,
.table-compact td {
    padding: 4px 6px;
    font-size: 9pt;
}

/* Totals column block (right-aligned table) */
.totals {
    width: 45%;
    margin-left: auto;
    margin-top: 12px;
}
.totals td { padding: 4px 6px; }
.totals .grand td {
    border-top: 2px solid #0f172a;
    font-weight: bold;
    font-size: 11pt;
    padding-top: 8px;
}

/* ---- Layout blocks ---- */
.panel {
    border: 1px solid #e5e7eb;
    background-color: #f8fafc;
    padding: 12px 14px;
}
.panel-ink {
    background-color: #0f172a;
    color: #ffffff;
    border: 0;
    padding: 12px 14px;
}

.badge {
    display: inline-block;
    font-size: 8.5pt;
    font-weight: bold;
    padding: 3px 8px;
    border: 1px solid #e5e7eb;
    background-color: #f3f4f6;
    color: #374151;
}
.badge-success {
    background-color: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.badge-warning {
    background-color: #fffbeb;
    color: #b45309;
    border-color: #fcd34d;
}
.badge-danger {
    background-color: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.badge-primary {
    background-color: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.label {
    font-size: 8pt;
    font-weight: bold;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.footer-note {
    margin-top: 28px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
    font-size: 8.5pt;
    color: #6b7280;
}

/* ---- Page helpers (mPDF) ---- */
.page-break, .break-before { page-break-before: always; }
.break-after  { page-break-after: always; }
.avoid-break  { page-break-inside: avoid; }

/* Keep a block together when possible */
.keep-together { page-break-inside: avoid; }

/* ---- Two-column via table (flex alternative) ---- */
.cols { width: 100%; border-collapse: collapse; }
.cols > tbody > tr > td { vertical-align: top; padding: 0; }
.cols-gap > tbody > tr > td { padding-right: 16px; }
.cols-gap > tbody > tr > td:last-child { padding-right: 0; padding-left: 16px; }

.nowrap { white-space: nowrap; }
CSS;
    }

    /**
     * Optional brand overrides — pass hex colours without #.
     *
     * @param  array{ink?: string, primary?: string, success?: string, muted?: string, border?: string}  $brand
     */
    public static function brand(array $brand = []): string
    {
        $ink = self::hex($brand['ink'] ?? '0f172a');
        $primary = self::hex($brand['primary'] ?? '1d4ed8');
        $success = self::hex($brand['success'] ?? '047857');
        $muted = self::hex($brand['muted'] ?? '6b7280');
        $border = self::hex($brand['border'] ?? 'e5e7eb');

        return <<<CSS
/* Brand overrides */
h1, h2, h3, h4, .text-ink { color: {$ink}; }
.text-primary, a { color: {$primary}; }
.text-success { color: {$success}; }
.text-muted, .label, .footer-note { color: {$muted}; }
.bg-ink, .table-lines th { background-color: {$ink}; }
.bg-primary { background-color: {$primary}; }
.border, .border-t, .border-b, hr, .panel, .footer-note {
    border-color: {$border};
}
.border-t-2, .border-b-2, .totals .grand td {
    border-color: {$ink};
}
CSS;
    }

    private static function hex(string $value): string
    {
        $value = ltrim(trim($value), '#');
        if (! preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
            throw new \InvalidArgumentException("Invalid hex colour: {$value}");
        }

        return '#' . strtolower($value);
    }
}
