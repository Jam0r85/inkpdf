<?php

declare(strict_types=1);

namespace InkPdf\Contracts;

use InkPdf\DocumentOptions;

interface PdfRenderer
{
    /**
     * Render HTML to raw PDF binary.
     *
     * @throws \InkPdf\Exceptions\RenderException
     */
    public function render(string $html, DocumentOptions $options): string;
}
