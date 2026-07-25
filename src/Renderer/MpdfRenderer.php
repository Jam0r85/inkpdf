<?php

declare(strict_types=1);

namespace InkPdf\Renderer;

use InkPdf\Contracts\PdfRenderer;
use InkPdf\Css\DocumentStylesheet;
use InkPdf\Css\HtmlStyler;
use InkPdf\DocumentOptions;
use InkPdf\Exceptions\RenderException;
use InkPdf\FontFace;
use InkPdf\Fonts\BundledFonts;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

/**
 * mPDF-backed HTML → PDF renderer.
 */
final class MpdfRenderer implements PdfRenderer
{
    public function __construct(
        private readonly HtmlStyler $styler = new HtmlStyler(),
    ) {
    }

    public function render(string $html, DocumentOptions $options): string
    {
        $prepared = $html;

        try {
            $prepared = $this->styler->apply(
                $html,
                $this->collectStylesheets($options),
                $options->normalizeCss,
            );
            $prepared = $this->sanitiseImages($prepared);

            $mpdf = $this->createMpdf($options);

            if ($options->title !== null) {
                $mpdf->SetTitle($options->title);
            }
            if ($options->author !== null) {
                $mpdf->SetAuthor($options->author);
            }
            if ($options->subject !== null) {
                $mpdf->SetSubject($options->subject);
            }

            $mpdf->WriteHTML($prepared);

            $output = $mpdf->Output('', Destination::STRING_RETURN);
            if (! is_string($output) || $output === '') {
                throw new RenderException('mPDF returned an empty PDF.');
            }

            $pages = $this->countPages($output);
            if ($options->maxPages > 0 && $pages > $options->maxPages) {
                $dump = $this->maybeDumpDebugHtml($prepared, $options, 'max-pages');
                $hint = $dump !== null ? " HTML dump: {$dump}" : '';

                throw new RenderException(
                    "InkPDF produced {$pages} pages (max allowed: {$options->maxPages}). "
                    .'This usually means a layout loop (min-height:297mm, page-break-after:always, or a bad image).'
                    .$hint
                );
            }

            return $output;
        } catch (RenderException $e) {
            if ($options->debug && ! str_contains($e->getMessage(), 'HTML dump:')) {
                $dump = $this->maybeDumpDebugHtml($prepared, $options, 'error');
                if ($dump !== null) {
                    throw new RenderException($e->getMessage().' HTML dump: '.$dump, previous: $e);
                }
            }

            throw $e;
        } catch (MpdfException $e) {
            $dump = $this->maybeDumpDebugHtml($prepared, $options, 'mpdf');
            $hint = $dump !== null ? ' HTML dump: '.$dump : '';

            throw new RenderException('Failed to render PDF: '.$e->getMessage().$hint, previous: $e);
        } catch (\Throwable $e) {
            $dump = $this->maybeDumpDebugHtml($prepared, $options, 'unexpected');
            $hint = $dump !== null ? ' HTML dump: '.$dump : '';

            throw new RenderException('Unexpected PDF render failure: '.$e->getMessage().$hint, previous: $e);
        }
    }

    /**
     * Drop remote / SVG images; ensure remaining imgs have numeric width & height.
     */
    private function sanitiseImages(string $html): string
    {
        $html = (string) preg_replace(
            '/<img\b[^>]*\bsrc=(["\'])https?:\/\/[^"\']+\1[^>]*>/i',
            '',
            $html
        );

        $html = (string) preg_replace(
            '/<img\b[^>]*\bsrc=(["\'])data:image\/svg\+xml[^"\']*\1[^>]*>/i',
            '',
            $html
        );

        // Strip exotic data URIs (only png/jpeg/gif/webp allowed — webp often fails in mPDF)
        $html = (string) preg_replace_callback(
            '/<img\b([^>]*)\bsrc=(["\'])(data:image\/([^;"\']+))[^"\']*\2([^>]*)>/i',
            static function (array $m): string {
                $type = strtolower($m[4]);
                if (! in_array($type, ['png', 'jpeg', 'jpg', 'gif'], true)) {
                    return '';
                }

                return '<img'.$m[1].'src='.$m[2].$m[3].$m[2].$m[5].'>';
            },
            $html
        );

        return (string) preg_replace_callback(
            '/<img\b([^>]*)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                if (preg_match('/\bwidth\s*=\s*(["\']?)\d+\1/i', $attrs) !== 1) {
                    $attrs .= ' width="160"';
                }
                if (preg_match('/\bheight\s*=\s*(["\']?)\d+\1/i', $attrs) !== 1) {
                    $attrs .= ' height="60"';
                }

                return '<img'.$attrs.'>';
            },
            $html
        );
    }

    private function countPages(string $pdf): int
    {
        if (preg_match_all('/\/Type\s*\/Page\b/', $pdf, $matches)) {
            return count($matches[0]);
        }

        return 1;
    }

    private function maybeDumpDebugHtml(string $html, DocumentOptions $options, string $tag): ?string
    {
        // Dump when debug is on, or always for max-pages so the guard is actionable.
        if (! $options->debug && $tag !== 'max-pages') {
            return null;
        }

        $dir = $options->debugPath !== '' ? $options->debugPath : ($options->tempDir.DIRECTORY_SEPARATOR.'debug');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        $path = $dir.DIRECTORY_SEPARATOR.'inkpdf-'.date('Ymd-His').'-'.$tag.'.html';
        if (@file_put_contents($path, $html) === false) {
            return null;
        }

        return $path;
    }

    /**
     * @return list<string>
     */
    private function collectStylesheets(DocumentOptions $options): array
    {
        $sheets = [];

        if ($options->useDocumentStyles) {
            $sheets[] = DocumentStylesheet::base();
            if ($options->brand !== null) {
                $sheets[] = DocumentStylesheet::brand($options->brand);
            }
        }

        foreach ($options->stylesheets as $css) {
            $sheets[] = $css;
        }

        return $sheets;
    }

    private function createMpdf(DocumentOptions $options): Mpdf
    {
        if (! is_dir($options->tempDir) && ! mkdir($options->tempDir, 0775, true) && ! is_dir($options->tempDir)) {
            throw new RenderException("Unable to create temp directory: {$options->tempDir}");
        }

        $fonts = $this->mergeBundledFonts($options->fonts);
        [$fontDirs, $fontData] = $this->buildFontConfig($fonts);

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();

        $config = [
            'mode' => 'utf-8',
            'format' => $options->paper->value,
            'orientation' => $options->orientation->value,
            'margin_left' => $options->marginsMm['left'],
            'margin_right' => $options->marginsMm['right'],
            'margin_top' => $options->marginsMm['top'],
            'margin_bottom' => $options->marginsMm['bottom'],
            'margin_header' => $options->marginHeaderMm,
            'margin_footer' => $options->marginFooterMm,
            'tempDir' => $options->tempDir,
            'default_font' => $this->resolveDefaultFont($options->defaultFont, $fonts),
            'default_font_size' => $options->defaultFontSize,
            'showImageErrors' => $options->showImageErrors,
            'fontDir' => array_values(array_unique(array_merge(
                $defaultConfig['fontDir'],
                $fontDirs,
                [BundledFonts::directory()],
            ))),
            'fontdata' => $fontData + $defaultFontConfig['fontdata'],
            'useSubstitutions' => true,
            'simpleTables' => false,
            'packTableData' => false,
            'shrink_tables_to_fit' => 0,
            'use_kwt' => false,
            'autoLangToFont' => false,
            'autoScriptToLang' => false,
        ];

        return new Mpdf($config);
    }

    /**
     * @param  list<FontFace>  $fonts
     * @return list<FontFace>
     */
    private function mergeBundledFonts(array $fonts): array
    {
        $hasInter = false;
        foreach ($fonts as $font) {
            if (strcasecmp($font->family, 'Inter') === 0) {
                $hasInter = true;
                break;
            }
        }

        if (! $hasInter && BundledFonts::interAvailable()) {
            $fonts = array_merge(BundledFonts::inter(), $fonts);
        }

        return $fonts;
    }

    /**
     * @param  list<FontFace>  $fonts
     * @return array{0: list<string>, 1: array<string, array<string, mixed>>}
     */
    private function buildFontConfig(array $fonts): array
    {
        $dirs = [];
        $data = [];

        foreach ($fonts as $font) {
            $dir = dirname($font->path);
            if (! in_array($dir, $dirs, true)) {
                $dirs[] = $dir;
            }

            $family = $font->mpdfFamily();
            $key = $font->mpdfStyleKey();
            $file = basename($font->path);

            if (! isset($data[$family])) {
                $data[$family] = [];
            }

            $data[$family][$key] = $file;
        }

        return [$dirs, $data];
    }

    /**
     * @param  list<FontFace>  $fonts
     */
    private function resolveDefaultFont(string $defaultFont, array $fonts): string
    {
        $normalised = strtolower(preg_replace('/\s+/', '', $defaultFont) ?? $defaultFont);

        if ($normalised === '' || $normalised === 'inter') {
            return BundledFonts::interAvailable() ? 'inter' : 'dejavusans';
        }

        foreach ($fonts as $font) {
            if (strcasecmp($font->family, $defaultFont) === 0 || $font->mpdfFamily() === $normalised) {
                return $font->mpdfFamily();
            }
        }

        return $normalised;
    }
}
