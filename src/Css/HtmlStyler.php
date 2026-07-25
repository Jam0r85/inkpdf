<?php

declare(strict_types=1);

namespace InkPdf\Css;

/**
 * Injects stylesheets into HTML documents before render.
 */
final class HtmlStyler
{
    /**
     * @param  list<string>  $stylesheets  Raw CSS chunks (not paths)
     */
    public function apply(string $html, array $stylesheets, bool $normalize = true): string
    {
        $stylesheets = array_values(array_filter(
            array_map(static fn (string $css): string => trim($css), $stylesheets),
            static fn (string $css): bool => $css !== ''
        ));

        if ($stylesheets !== []) {
            $combined = implode("\n\n", $stylesheets);
            $block = "<style type=\"text/css\">\n{$combined}\n</style>";
            $html = $this->injectHead($html, $block);
        }

        if ($normalize) {
            $html = (new CssNormalizer())->normalizeHtml($html);
        }

        return $html;
    }

    private function injectHead(string $html, string $block): string
    {
        if (preg_match('/<head\b[^>]*>/i', $html)) {
            return (string) preg_replace('/(<head\b[^>]*>)/i', '$1' . "\n" . $block, $html, 1);
        }

        if (preg_match('/<html\b[^>]*>/i', $html)) {
            return (string) preg_replace(
                '/(<html\b[^>]*>)/i',
                '$1<head>' . $block . '</head>',
                $html,
                1
            );
        }

        return '<html><head>' . $block . '</head><body>' . $html . '</body></html>';
    }
}
