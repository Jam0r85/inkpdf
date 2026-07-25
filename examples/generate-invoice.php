<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use InkPdf\InkPdf;

$template = dirname(__DIR__) . '/resources/templates/invoice.html';
$outDir = dirname(__DIR__) . '/examples/output';
$outFile = $outDir . '/invoice-INV-1001.pdf';

$path = InkPdf::loadFile($template)
    ->setPaper('A4')
    ->setMargins(14)
    ->setDefaultFont('DejaVu Sans', 10)
    ->setMeta(
        title: 'Invoice INV-1001',
        author: 'InkPDF Example',
        subject: 'Sample invoice',
    )
    ->save($outFile);

echo "Wrote {$path}" . PHP_EOL;
