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
 *
 * Chosen over dompdf for stronger Unicode, font embedding, and table-heavy
 * business documents (invoices, payment advices, statements).
 */
final class MpdfRenderer implements PdfRenderer
{
    public function __construct(
        private readonly HtmlStyler $styler = new HtmlStyler(),
    ) {
    }

    public function render(string $html, DocumentOptions $options): string
    {
        try {
            $html = $this->styler->apply(
                $html,
                $this->collectStylesheets($options),
                $options->normalizeCss,
            );

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

            $mpdf->WriteHTML($html);

            $output = $mpdf->Output('', Destination::STRING_RETURN);
            if (! is_string($output) || $output === '') {
                throw new RenderException('mPDF returned an empty PDF.');
            }

            return $output;
        } catch (RenderException $e) {
            throw $e;
        } catch (MpdfException $e) {
            throw new RenderException('Failed to render PDF: ' . $e->getMessage(), previous: $e);
        } catch (\Throwable $e) {
            throw new RenderException('Unexpected PDF render failure: ' . $e->getMessage(), previous: $e);
        }
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
            'margin_header' => 8,
            'margin_footer' => 8,
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
            'packTableData' => true,
            // 0 = never shrink tables (shrink loops can create blank pages)
            'shrink_tables_to_fit' => 0,
            // keep-with-table can page-break-loop on complex float/table hybrids
            'use_kwt' => false,
            'autoLangToFont' => false,
            'autoScriptToLang' => false,
        ];

        return new Mpdf($config);
    }

    /**
     * Always register bundled Inter unless the caller already defined that family.
     *
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
