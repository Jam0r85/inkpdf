<?php

declare(strict_types=1);

namespace InkPdf\Laravel;

use Illuminate\Support\ServiceProvider;
use InkPdf\DocumentOptions;
use InkPdf\InkPdf;
use InkPdf\PaperSize;

/**
 * Optional Laravel bridge: config, defaults, and a simple view helper.
 */
final class InkPdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/inkpdf.php', 'inkpdf');

        $this->app->singleton('inkpdf', function (): InkPdf {
            return new InkPdf;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/inkpdf.php' => config_path('inkpdf.php'),
            ], 'inkpdf-config');
        }
    }

    /**
     * Build a document using config defaults, then load a Blade view as HTML.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options  override config (margins, max_pages, debug, …)
     */
    public static function documentFromView(string $view, array $data = [], array $options = []): \InkPdf\PdfDocument
    {
        $cfg = function_exists('config') ? (array) config('inkpdf', []) : [];

        $margins = array_merge(
            ['top' => 10.0, 'right' => 10.0, 'bottom' => 28.0, 'left' => 10.0],
            (array) ($cfg['margins'] ?? []),
            array_filter([
                'top' => $options['margin_top'] ?? null,
                'right' => $options['margin_right'] ?? null,
                'bottom' => $options['margin_bottom'] ?? null,
                'left' => $options['margin_left'] ?? null,
            ], static fn ($v) => $v !== null),
        );

        $html = view($view, $data)->render();

        $doc = InkPdf::loadHtml($html)
            ->setPaper($options['format'] ?? PaperSize::A4, $options['orientation'] ?? 'portrait')
            ->setMargins($margins)
            ->setFooterMargin((float) ($options['margin_footer'] ?? $cfg['margin_footer'] ?? 22))
            ->setHeaderMargin((float) ($options['margin_header'] ?? $cfg['margin_header'] ?? 10))
            ->setDefaultFont(
                (string) ($options['default_font'] ?? $cfg['default_font'] ?? 'Inter'),
                (float) ($options['font_size'] ?? $cfg['default_font_size'] ?? 11),
=======
                (float) ($options['font_size'] ?? $cfg['default_font_size'] ?? 11),
            )
            ->setMaxPages((int) ($options['max_pages'] ?? $cfg['max_pages'] ?? 100))
            ->setDebug((bool) ($options['debug'] ?? $cfg['debug'] ?? false));

        $temp = $options['temp_dir'] ?? $cfg['temp_dir'] ?? null;
        if (is_string($temp) && $temp !== '') {
            $doc->setTempDir($temp);
        } elseif (function_exists('storage_path')) {
            $doc->setTempDir(storage_path('app/tmp/inkpdf'));
        }

        $debugPath = $options['debug_path'] ?? $cfg['debug_path'] ?? null;
        if (is_string($debugPath) && $debugPath !== '') {
            $doc->setDebugPath($debugPath);
        } elseif (function_exists('storage_path')) {
            $doc->setDebugPath(storage_path('logs/inkpdf'));
        }

        if (isset($options['title']) && is_string($options['title']) && $options['title'] !== '') {
            $doc->setMeta(title: $options['title']);
        }
        if (isset($options['author']) && is_string($options['author']) && $options['author'] !== '') {
            $doc->setMeta(author: $options['author']);
        }

        if (! ($options['normalize_css'] ?? false)) {
            $doc->normalizeCss(false);
        }

        return $doc;
    }
}
