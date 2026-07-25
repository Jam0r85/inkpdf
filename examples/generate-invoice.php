<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use InkPdf\InkPdf;

$template = dirname(__DIR__) . '/resources/templates/invoice.html';
$outFile = dirname(__DIR__) . '/examples/output/invoice-INV-1001.pdf';

$path = InkPdf::loadFile($template)
    ->setPaper('A4')
    ->setMargins(14)
    ->setDefaultFont('Inter', 10)
    ->withDocumentStyles()
    ->withBrand(['ink' => '0f172a', 'primary' => '1d4ed8'])
    ->setMeta(
        title: 'Invoice INV-1001',
        author: 'InkPDF Example',
        subject: 'Sample invoice',
    )
    ->save($outFile);

echo "Wrote {$path}" . PHP_EOL;
