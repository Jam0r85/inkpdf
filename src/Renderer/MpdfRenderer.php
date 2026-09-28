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
use Mpdf\Container\SimpleContainer;
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
            $prepared = $this->sanitiseImages($this->stripLinkedResources($prepared));

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

            // mPDF refuses HTML longer than pcre.backtrack_limit; embedded photos easily pass the
            // 1MB default. Raise it for this render only.
            $backtrackLimit = ini_get('pcre.backtrack_limit');
            $needed = max(1_000_000, strlen($prepared) * 2);
            if ($backtrackLimit !== false && (int) $backtrackLimit < $needed) {
                ini_set('pcre.backtrack_limit', (string) $needed);
            }

            try {
                $mpdf->WriteHTML($prepared);
            } finally {
                if ($backtrackLimit !== false) {
                    ini_set('pcre.backtrack_limit', $backtrackLimit);
                }
            }

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
     *
     * One linear pass over the <img> tags: `[^>]*` can't overrun a tag, and the src is read with
     * strpos rather than a regex, so a large base64 photo never runs into pcre.backtrack_limit.
     */
    private function sanitiseImages(string $html): string
    {
        return $this->replaceCallback(
            '/<img\b[^>]*>/i',
            function (array $m): string {
                $tag = $m[0];
                [$src, $rest] = $this->splitSrc($tag);

                if ($src !== null && ! $this->isAllowedImageSource($src)) {
                    return '';
                }

                // Look for width/height outside the (possibly huge) src value.
                $size = '';
                if (preg_match('/\bwidth\s*=\s*(["\']?)\d+\1/i', $rest) !== 1) {
                    $size .= ' width="160"';
                }
                if (preg_match('/\bheight\s*=\s*(["\']?)\d+\1/i', $rest) !== 1) {
                    $size .= ' height="60"';
                }

                if ($size === '') {
                    return $tag;
                }

                $close = str_ends_with($tag, '/>') ? -2 : -1;

                return rtrim(substr($tag, 0, $close)).$size.substr($tag, $close);
            },
            $html
        );
    }

    /**
     * The tag's quoted src value, and the tag with that value taken out.
     *
     * @return array{0: string|null, 1: string}
     */
    private function splitSrc(string $tag): array
    {
        if (preg_match('/\ssrc\s*=\s*(["\'])/i', $tag, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return [null, $tag];
        }

        $quote = $m[1][0];
        $start = $m[1][1] + 1;
        $end = strpos($tag, $quote, $start);

        if ($end === false) {
            return [null, $tag];
        }

        return [substr($tag, $start, $end - $start), substr($tag, 0, $m[0][1]).substr($tag, $end + 1)];
    }

    /**
     * Local file paths and png/jpeg/gif data URIs. Never a URL of any kind (`http:`, `//host`,
     * `file:`, `ftp:`…), SVG or webp (often fails in mPDF).
     */
    private function isAllowedImageSource(string $src): bool
    {
        $src = ltrim($src);

        if (strncasecmp($src, 'data:', 5) !== 0) {
            return LocalOnlyContentLoader::isLocalPath($src);
        }

        $type = strtolower(substr($src, 11, (int) strcspn($src, ';,', 11)));

        return strncasecmp($src, 'data:image/', 11) === 0 && in_array($type, ['png', 'jpeg', 'jpg', 'gif'], true);
    }

    /**
     * `<link>` tags go: a stylesheet or anything else they point at is a fetch. Styles come from
     * the document's own `<style>` blocks and the stylesheets the app passes.
     */
    private function stripLinkedResources(string $html): string
    {
        $result = preg_replace('/<link\b[^>]*>/i', '', $html);

        if ($result === null) {
            throw new RenderException('InkPDF could not process the HTML links: '.preg_last_error_msg().'.');
        }

        return $result;
    }

    /** preg_replace_callback that fails loudly instead of silently emptying the document. */
    private function replaceCallback(string $pattern, callable $callback, string $html): string
    {
        $result = preg_replace_callback($pattern, $callback, $html);

        if ($result === null) {
            throw new RenderException('InkPDF could not process the HTML images: '.preg_last_error_msg().'.');
        }

        return $result;
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
        // The HTML holds whatever the document does (names, addresses, bank details), so it is
        // only ever written to disk when debug is on.
        if (! $options->debug) {
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

        // Nothing in the document may make mPDF fetch a resource: data URIs and local paths only.
        return new Mpdf($config, new SimpleContainer([
            'localContentLoader' => new LocalOnlyContentLoader(),
            'httpClient' => new NoRemoteHttpClient(),
        ]));
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
