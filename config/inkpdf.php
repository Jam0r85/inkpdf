<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default typeface
    |--------------------------------------------------------------------------
    |
    | Bundled Inter is registered automatically. Fall back to DejaVu Sans if
    | Inter is unavailable.
    |
    */
    'default_font' => env('INKPDF_DEFAULT_FONT', 'Inter'),

    'default_font_size' => (float) env('INKPDF_DEFAULT_FONT_SIZE', 11),

    /*
    |--------------------------------------------------------------------------
    | Page margins (mm)
    |--------------------------------------------------------------------------
    */
    'margins' => [
        'top' => (float) env('INKPDF_MARGIN_TOP', 10),
        'right' => (float) env('INKPDF_MARGIN_RIGHT', 10),
        'bottom' => (float) env('INKPDF_MARGIN_BOTTOM', 28),
        'left' => (float) env('INKPDF_MARGIN_LEFT', 10),
    ],

    'margin_header' => (float) env('INKPDF_MARGIN_HEADER', 10),

    'margin_footer' => (float) env('INKPDF_MARGIN_FOOTER', 22),

    /*
    |--------------------------------------------------------------------------
    | Max pages guard
    |--------------------------------------------------------------------------
    |
    | mPDF layout bugs can invent hundreds of blank pages. Renders that exceed
    | this count are aborted with a clear exception. Raise for long reports.
    | Set to 0 to disable the guard.
    |
    */
    'max_pages' => (int) env('INKPDF_MAX_PAGES', 100),

    /*
    |--------------------------------------------------------------------------
    | Debug mode
    |--------------------------------------------------------------------------
    |
    | When true, failed renders write the HTML payload to storage (or temp)
    | and include the dump path in the exception message.
    |
    */
    'debug' => (bool) env('INKPDF_DEBUG', false),

    'debug_path' => env('INKPDF_DEBUG_PATH'), // default: storage_path('logs/inkpdf') when Laravel is present

    /*
    |--------------------------------------------------------------------------
    | Temporary directory for mPDF
    |--------------------------------------------------------------------------
    */
    'temp_dir' => env('INKPDF_TEMP_DIR'),

];
