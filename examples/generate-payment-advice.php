<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use InkPdf\InkPdf;

$template = dirname(__DIR__) . '/resources/templates/payment-advice.html';
$outFile = dirname(__DIR__) . '/examples/output/payment-advice-PA-2044.pdf';

$path = InkPdf::loadFile($template)
    ->setPaper('A4')
    ->setMargins(14)
    ->setDefaultFont('DejaVu Sans', 10)
    ->withDocumentStyles()
    ->setMeta(
        title: 'Payment advice PA-2044',
        author: 'InkPDF Example',
        subject: 'Sample payment advice',
    )
    ->save($outFile);

echo "Wrote {$path}" . PHP_EOL;
