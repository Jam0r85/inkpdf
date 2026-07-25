<?php

declare(strict_types=1);

namespace InkPdf\Fonts;

use InkPdf\FontFace;

/**
 * Fonts shipped with InkPDF.
 *
 * Inter (SIL Open Font License 1.1) is the default document face.
 * @see https://github.com/rsms/inter
 */
final class BundledFonts
{
    public static function directory(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'fonts';
    }

    /**
     * Inter family faces available out of the box.
     *
     * @return list<FontFace>
     */
    public static function inter(): array
    {
        $dir = self::directory();

        $faces = [
            ['Inter-Regular.ttf', 'normal', 'normal'],
            ['Inter-Bold.ttf', 'normal', 'bold'],
            ['Inter-Italic.ttf', 'italic', 'normal'],
            ['Inter-BoldItalic.ttf', 'italic', 'bold'],
        ];

        $out = [];
        foreach ($faces as [$file, $style, $weight]) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                $out[] = new FontFace('Inter', $path, $style, $weight);
            }
        }

        return $out;
    }

    public static function interAvailable(): bool
    {
        return is_file(self::directory() . DIRECTORY_SEPARATOR . 'Inter-Regular.ttf');
    }
}
