<?php

declare(strict_types=1);

namespace InkPdf;

use InkPdf\Contracts\PdfRenderer;
use InkPdf\Renderer\MpdfRenderer;

/**
 * Static entry-point for InkPDF.
 *
 * @example
 * ```php
 * use InkPdf\InkPdf;
 *
 * $pdf = InkPdf::loadHtml($html)
 *     ->setPaper('A4')
 *     ->setDefaultFont('Inter') // bundled default
 *     ->addFont('Brand', __DIR__.'/fonts/Brand-Regular.ttf')
 *     ->addFont('Brand', __DIR__.'/fonts/Brand-Bold.ttf', weight: 'bold')
 *     ->setMeta(title: 'Invoice INV-1001')
 *     ->save(storage_path('app/invoices/INV-1001.pdf'));
 * ```
 */
final class InkPdf
{
    /**
     * Start a new document from an HTML string.
     * Default typeface is bundled Inter (SIL OFL).
     */
    public static function loadHtml(string $html, ?PdfRenderer $renderer = null): PdfDocument
    {
        return PdfDocument::make($renderer)->loadHtml($html);
    }

    /**
     * Laravel helper: render a Blade view to a configured PdfDocument (requires the service provider).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     */
    public static function view(string $view, array $data = [], array $options = []): PdfDocument
    {
        if (! class_exists(\InkPdf\Laravel\InkPdfServiceProvider::class)) {
            throw new \RuntimeException('InkPdf::view() requires the Laravel bridge.');
        }

        return \InkPdf\Laravel\InkPdfServiceProvider::documentFromView($view, $data, $options);
    }

    /**
     * Start a new document from an HTML file path.
     */
    public static function loadFile(string $path, ?PdfRenderer $renderer = null): PdfDocument
    {
        return PdfDocument::make($renderer)->loadFile($path);
    }

    /**
     * Create an empty document builder.
     */
    public static function make(?PdfRenderer $renderer = null): PdfDocument
    {
        return PdfDocument::make($renderer);
    }

    /**
     * Default renderer instance (mPDF).
     */
    public static function renderer(): PdfRenderer
    {
        return new MpdfRenderer();
    }
}
