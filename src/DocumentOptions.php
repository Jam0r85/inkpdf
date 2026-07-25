<?php

declare(strict_types=1);

namespace InkPdf;

/**
 * Render options for an InkPDF document.
 */
final class DocumentOptions
{
    /**
     * @param  array{top: float, right: float, bottom: float, left: float}  $marginsMm
     * @param  list<FontFace>  $fonts
     * @param  list<string>  $stylesheets  Extra CSS strings injected before render
     * @param  array{ink?: string, primary?: string, success?: string, muted?: string, border?: string}|null  $brand
     */
    public function __construct(
        public PaperSize $paper = PaperSize::A4,
        public Orientation $orientation = Orientation::Portrait,
        public array $marginsMm = [
            'top' => 15.0,
            'right' => 15.0,
            'bottom' => 15.0,
            'left' => 15.0,
        ],
        public string $defaultFont = 'Inter',
        public float $defaultFontSize = 10.0,
        public string $tempDir = '',
        public array $fonts = [],
        public ?string $title = null,
        public ?string $author = null,
        public ?string $subject = null,
        public bool $showImageErrors = false,
        public bool $useDocumentStyles = false,
        public bool $normalizeCss = true,
        public array $stylesheets = [],
        public ?array $brand = null,
    ) {
        if ($this->tempDir === '') {
            $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inkpdf';
        }
    }

    public function withPaper(PaperSize|string $paper, Orientation|string|null $orientation = null): self
    {
        $clone = clone $this;
        $clone->paper = self::resolvePaper($paper);

        if ($orientation !== null) {
            $clone->orientation = self::resolveOrientation($orientation);
        }

        return $clone;
    }

    /**
     * @param  array{top?: float, right?: float, bottom?: float, left?: float}|float|int  $margins
     */
    public function withMargins(array|float|int $margins): self
    {
        $clone = clone $this;

        if (is_float($margins) || is_int($margins)) {
            $value = (float) $margins;
            $clone->marginsMm = [
                'top' => $value,
                'right' => $value,
                'bottom' => $value,
                'left' => $value,
            ];

            return $clone;
        }

        $clone->marginsMm = array_merge($clone->marginsMm, $margins);

        return $clone;
    }

    public function withDefaultFont(string $family, ?float $sizePt = null): self
    {
        $clone = clone $this;
        $clone->defaultFont = $family;
        if ($sizePt !== null) {
            $clone->defaultFontSize = $sizePt;
        }

        return $clone;
    }

    public function withMeta(?string $title = null, ?string $author = null, ?string $subject = null): self
    {
        $clone = clone $this;
        $clone->title = $title ?? $clone->title;
        $clone->author = $author ?? $clone->author;
        $clone->subject = $subject ?? $clone->subject;

        return $clone;
    }

    public function withFont(FontFace $font): self
    {
        $clone = clone $this;
        $clone->fonts[] = $font;

        return $clone;
    }

    public function withDocumentStyles(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->useDocumentStyles = $enabled;

        return $clone;
    }

    public function withNormalizeCss(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->normalizeCss = $enabled;

        return $clone;
    }

    public function withStylesheet(string $css): self
    {
        $clone = clone $this;
        $clone->stylesheets[] = $css;

        return $clone;
    }

    /**
     * @param  array{ink?: string, primary?: string, success?: string, muted?: string, border?: string}  $brand
     */
    public function withBrand(array $brand): self
    {
        $clone = clone $this;
        $clone->brand = $brand;
        $clone->useDocumentStyles = true;

        return $clone;
    }

    public static function resolvePaper(PaperSize|string $paper): PaperSize
    {
        if ($paper instanceof PaperSize) {
            return $paper;
        }

        return match (strtolower(trim($paper))) {
            'a4' => PaperSize::A4,
            'a5' => PaperSize::A5,
            'letter' => PaperSize::Letter,
            'legal' => PaperSize::Legal,
            default => throw new \InvalidArgumentException("Unknown paper size: {$paper}"),
        };
    }

    public static function resolveOrientation(Orientation|string $orientation): Orientation
    {
        if ($orientation instanceof Orientation) {
            return $orientation;
        }

        return match (strtolower(trim($orientation))) {
            'p', 'portrait' => Orientation::Portrait,
            'l', 'landscape' => Orientation::Landscape,
            default => throw new \InvalidArgumentException("Unknown orientation: {$orientation}"),
        };
    }
}
