<?php

declare(strict_types=1);

namespace InkPdf;

/**
 * Common paper sizes for business documents.
 */
enum PaperSize: string
{
    case A4 = 'A4';
    case A5 = 'A5';
    case Letter = 'Letter';
    case Legal = 'Legal';

    /**
     * @return array{0: float, 1: float} Width and height in millimetres.
     */
    public function dimensionsMm(): array
    {
        return match ($this) {
            self::A4 => [210.0, 297.0],
            self::A5 => [148.0, 210.0],
            self::Letter => [215.9, 279.4],
            self::Legal => [215.9, 355.6],
        };
    }
}
