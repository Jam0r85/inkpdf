<?php

declare(strict_types=1);

namespace InkPdf;

use InkPdf\Contracts\PdfRenderer;
use InkPdf\Exceptions\InkPdfException;
use InkPdf\Renderer\MpdfRenderer;

/**
 * Fluent document builder: configure options, then render to string/file/download.
 */
final class PdfDocument
{
    private DocumentOptions $options;

    private string $html = '';

    public function __construct(
        private readonly PdfRenderer $renderer = new MpdfRenderer(),
        ?DocumentOptions $options = null,
    ) {
        $this->options = $options ?? new DocumentOptions();
    }

    public static function make(?PdfRenderer $renderer = null): self
    {
        return new self($renderer ?? new MpdfRenderer());
    }

    public function loadHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function loadFile(string $path): self
    {
        if (! is_file($path)) {
            throw new InkPdfException("HTML file not found: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InkPdfException("Unable to read HTML file: {$path}");
        }

        $this->html = $contents;

        return $this;
    }

    public function setPaper(PaperSize|string $paper, Orientation|string $orientation = Orientation::Portrait): self
    {
        $this->options = $this->options->withPaper($paper, $orientation);

        return $this;
    }

    /**
     * @param  array{top?: float, right?: float, bottom?: float, left?: float}|float  $marginsMm
     */
    public function setMargins(array|float $marginsMm): self
    {
        $this->options = $this->options->withMargins($marginsMm);

        return $this;
    }

    public function setDefaultFont(string $family, ?float $sizePt = null): self
    {
        $this->options = $this->options->withDefaultFont($family, $sizePt);

        return $this;
    }

    public function setMeta(?string $title = null, ?string $author = null, ?string $subject = null): self
    {
        $this->options = $this->options->withMeta($title, $author, $subject);

        return $this;
    }

    public function setTempDir(string $path): self
    {
        $this->options->tempDir = $path;

        return $this;
    }

    /**
     * Register a TTF/OTF font for use in CSS font-family.
     *
     * @param  string|int  $weight  e.g. 'normal', 'bold', 400, 700
     */
    public function addFont(
        string $family,
        string $path,
        string $style = 'normal',
        string|int $weight = 'normal',
    ): self {
        $this->options = $this->options->withFont(new FontFace($family, $path, $style, $weight));

        return $this;
    }

    public function options(): DocumentOptions
    {
        return $this->options;
    }

    /**
     * Render and return raw PDF binary.
     */
    public function output(): string
    {
        $this->assertHasHtml();

        return $this->renderer->render($this->html, $this->options);
    }

    /**
     * Render and write to a filesystem path. Creates parent directories if needed.
     */
    public function save(string $path): string
    {
        $pdf = $this->output();
        $dir = dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new InkPdfException("Unable to create directory: {$dir}");
        }

        if (file_put_contents($path, $pdf) === false) {
            throw new InkPdfException("Unable to write PDF to: {$path}");
        }

        return $path;
    }

    /**
     * Render and emit download headers (for classic PHP front controllers).
     * In Laravel prefer: response($pdf->output(), 200, [...]).
     */
    public function download(string $filename = 'document.pdf'): never
    {
        $pdf = $this->output();
        $filename = $this->safeFilename($filename);

        if (! headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . (string) strlen($pdf));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }

        echo $pdf;
        exit;
    }

    /**
     * Render and stream inline in the browser.
     */
    public function stream(string $filename = 'document.pdf'): never
    {
        $pdf = $this->output();
        $filename = $this->safeFilename($filename);

        if (! headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Length: ' . (string) strlen($pdf));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }

        echo $pdf;
        exit;
    }

    private function assertHasHtml(): void
    {
        if (trim($this->html) === '') {
            throw new InkPdfException('No HTML loaded. Call loadHtml() or loadFile() first.');
        }
    }

    private function safeFilename(string $filename): string
    {
        $filename = basename($filename);
        if (! str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return preg_replace('/[^\w.\-]+/', '_', $filename) ?: 'document.pdf';
    }
}
