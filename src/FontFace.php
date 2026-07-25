<?php

declare(strict_types=1);

namespace InkPdf;

/**
 * A registered TrueType / OpenType font face.
 */
final readonly class FontFace
{
    public function __construct(
        public string $family,
        public string $path,
        public string $style = 'normal',
        public string|int $weight = 'normal',
    ) {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("Font file not found: {$path}");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['ttf', 'otf', 'ttc'], true)) {
            throw new \InvalidArgumentException(
                "Unsupported font type '.{$extension}'. Use TTF, OTF, or TTC."
            );
        }
    }

    /**
     * mPDF font-style key: R, B, I, or BI.
     */
    public function mpdfStyleKey(): string
    {
        $bold = $this->isBold();
        $italic = $this->isItalic();

        return match (true) {
            $bold && $italic => 'BI',
            $bold => 'B',
            $italic => 'I',
            default => 'R',
        };
    }

    public function isBold(): bool
    {
        if (is_int($this->weight)) {
            return $this->weight >= 600;
        }

        return in_array(strtolower((string) $this->weight), ['bold', 'bolder', '700', '800', '900'], true);
    }

    public function isItalic(): bool
    {
        return in_array(strtolower($this->style), ['italic', 'oblique'], true);
    }

    /**
     * Normalised family name for mPDF (lowercase, no spaces).
     */
    public function mpdfFamily(): string
    {
        return strtolower(preg_replace('/\s+/', '', $this->family) ?? $this->family);
    }
}
