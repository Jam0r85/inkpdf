<?php

declare(strict_types=1);

namespace InkPdf\Css;

/**
 * Downlevels modern CSS so mPDF is more likely to apply styles correctly.
 *
 * Not a full browser engine — focuses on the pain points that show up in
 * real templates: custom properties, modern colour syntax, and a few aliases.
 */
final class CssNormalizer
{
    /**
     * Normalize a full HTML document (inline <style> blocks + style="").
     */
    public function normalizeHtml(string $html): string
    {
        $variables = $this->collectVariables($html);

        $html = (string) preg_replace_callback(
            '/(<style\b[^>]*>)(.*?)(<\/style>)/is',
            function (array $m) use ($variables): string {
                return $m[1] . $this->normalizeCss($m[2], $variables) . $m[3];
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '/\sstyle=("|\')(.*?)\1/is',
            function (array $m) use ($variables): string {
                $css = $this->normalizeCss($m[2], $variables);

                return ' style=' . $m[1] . $css . $m[1];
            },
            $html
        );

        return $html;
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function normalizeCss(string $css, array $variables = []): string
    {
        if ($variables === []) {
            $variables = $this->collectVariablesFromCss($css);
        }

        $css = $this->stripNoisyAtRules($css);
        $css = $this->expandVariables($css, $variables);
        $css = $this->normalizeColors($css);
        $css = $this->normalizeAliases($css);
        $css = $this->dropUnsupportedDeclarations($css);

        return trim($css);
    }

    /**
     * @return array<string, string>
     */
    private function collectVariables(string $html): array
    {
        $vars = [];
        if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $matches)) {
            foreach ($matches[1] as $block) {
                $vars = array_merge($vars, $this->collectVariablesFromCss($block));
            }
        }

        return $vars;
    }

    /**
     * @return array<string, string>
     */
    private function collectVariablesFromCss(string $css): array
    {
        $vars = [];

        if (preg_match_all('/(?::root|html)\s*\{([^}]*)\}/i', $css, $blocks)) {
            foreach ($blocks[1] as $body) {
                if (preg_match_all('/(--[a-zA-Z0-9-_]+)\s*:\s*([^;]+);?/u', $body, $m, PREG_SET_ORDER)) {
                    foreach ($m as $row) {
                        $vars[trim($row[1])] = trim($row[2]);
                    }
                }
            }
        }

        return $vars;
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function expandVariables(string $css, array $variables): string
    {
        for ($i = 0; $i < 5; $i++) {
            $replaced = (string) preg_replace_callback(
                '/var\(\s*(--[a-zA-Z0-9-_]+)\s*(?:,\s*([^)]+))?\)/u',
                static function (array $m) use ($variables): string {
                    $name = $m[1];
                    if (isset($variables[$name])) {
                        return $variables[$name];
                    }

                    return isset($m[2]) ? trim($m[2]) : 'inherit';
                },
                $css
            );

            if ($replaced === $css) {
                break;
            }
            $css = $replaced;
        }

        // Remove :root / html custom-property blocks once expanded.
        $css = (string) preg_replace('/(?::root|html)\s*\{[^}]*\}/i', '', $css);

        return $css;
    }

    private function normalizeColors(string $css): string
    {
        // Space-separated rgb(a)
        $css = (string) preg_replace_callback(
            '/rgba?\(\s*([0-9.]+%?)\s+([0-9.]+%?)\s+([0-9.]+%?)(?:\s*\/\s*([0-9.]+%?))?\s*\)/i',
            static function (array $m): string {
                $r = $m[1];
                $g = $m[2];
                $b = $m[3];
                if (isset($m[4]) && $m[4] !== '') {
                    $a = str_ends_with($m[4], '%')
                        ? (float) $m[4] / 100
                        : (float) $m[4];
                    if ($a < 1) {
                        return self::blendOnWhite($r, $g, $b, $a);
                    }
                }

                return "rgb({$r}, {$g}, {$b})";
            },
            $css
        );

        // Space-separated hsl(a)
        $css = (string) preg_replace_callback(
            '/hsla?\(\s*([0-9.]+)\s+([0-9.]+)%\s+([0-9.]+)%(?:\s*\/\s*([0-9.]+%?))?\s*\)/i',
            static function (array $m): string {
                $rgb = self::hslToRgb((float) $m[1], (float) $m[2], (float) $m[3]);
                $a = 1.0;
                if (isset($m[4]) && $m[4] !== '') {
                    $a = str_ends_with($m[4], '%')
                        ? (float) $m[4] / 100
                        : (float) $m[4];
                }
                if ($a < 1) {
                    return self::blendOnWhite((string) $rgb[0], (string) $rgb[1], (string) $rgb[2], $a);
                }

                return sprintf('rgb(%d, %d, %d)', $rgb[0], $rgb[1], $rgb[2]);
            },
            $css
        );

        // oklch — pragmatic mapping for greys / brand tokens
        $css = (string) preg_replace_callback(
            '/oklch\(\s*([0-9.]+%?)\s+([0-9.]+)\s+([0-9.]+)(?:\s*\/\s*([0-9.]+%?))?\s*\)/i',
            static function (array $m): string {
                $l = str_ends_with($m[1], '%') ? (float) $m[1] / 100 : (float) $m[1];
                $c = (float) $m[2];
                if ($c < 0.03) {
                    $v = (int) max(0, min(255, round($l * 255)));

                    return sprintf('rgb(%d, %d, %d)', $v, $v, $v);
                }
                if ($l < 0.35) {
                    return 'rgb(15, 23, 42)';
                }
                if ($l > 0.85) {
                    return 'rgb(248, 250, 252)';
                }

                return 'rgb(29, 78, 216)';
            },
            $css
        );

        return $css;
    }

    private function normalizeAliases(string $css): string
    {
        $css = (string) preg_replace('/\binline-flex\b/i', 'inline-block', $css);
        $css = (string) preg_replace('/display\s*:\s*flex\b/i', 'display: block', $css);
        $css = (string) preg_replace('/display\s*:\s*grid\b/i', 'display: block', $css);

        return $css;
    }

    private function dropUnsupportedDeclarations(string $css): string
    {
        $props = [
            'backdrop-filter',
            'filter',
            'mix-blend-mode',
            'grid-template-columns',
            'grid-template-rows',
            'grid-template-areas',
            'grid-column',
            'grid-row',
            'grid-auto-flow',
            'flex-direction',
            'flex-wrap',
            'flex-grow',
            'flex-shrink',
            'flex-basis',
            'flex',
            'justify-content',
            'align-items',
            'align-content',
            'align-self',
            'order',
            'gap',
            'row-gap',
            'column-gap',
            'place-items',
            'place-content',
            'aspect-ratio',
            'object-fit',
            'object-position',
            'container-type',
            'container-name',
            'will-change',
            'transform',
            'transition',
            'animation',
        ];

        $pattern = '/\b(?:' . implode('|', array_map('preg_quote', $props)) . ')\s*:[^;}{]+;?/i';

        return (string) preg_replace($pattern, '', $css);
    }

    private function stripNoisyAtRules(string $css): string
    {
        // Remove @layer / @supports / @container / @keyframes blocks (non-nested approximation).
        foreach (['layer', 'supports', 'container', 'keyframes'] as $rule) {
            $css = $this->stripAtRuleBlocks($css, $rule);
        }

        // Unwrap simple @media print { ... }
        $css = (string) preg_replace('/@media[^{]*\bprint\b[^{]*\{/i', '', $css);

        // Drop remaining @media ... { by removing the opener; best-effort brace balance below.
        $css = $this->stripAtRuleBlocks($css, 'media');

        return $css;
    }

    private function stripAtRuleBlocks(string $css, string $ruleName): string
    {
        $pattern = '/@' . preg_quote($ruleName, '/') . '\b/i';
        while (preg_match($pattern, $css, $m, PREG_OFFSET_CAPTURE)) {
            $start = $m[0][1];
            $brace = strpos($css, '{', $start);
            if ($brace === false) {
                break;
            }
            $depth = 0;
            $len = strlen($css);
            $end = null;
            for ($i = $brace; $i < $len; $i++) {
                $ch = $css[$i];
                if ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $end = $i;
                        break;
                    }
                }
            }
            if ($end === null) {
                break;
            }

            // For @media print we already unwrapped openers; for others, drop whole block.
            if (strtolower($ruleName) === 'media') {
                // If this was print and we only removed opener earlier, just drop orphan close — skip.
                $css = substr($css, 0, $start) . substr($css, $end + 1);
            } else {
                $css = substr($css, 0, $start) . substr($css, $end + 1);
            }
        }

        return $css;
    }

    private static function blendOnWhite(string $r, string $g, string $b, float $a): string
    {
        $to = static function (string $channel): int {
            if (str_ends_with($channel, '%')) {
                return (int) round(((float) $channel) / 100 * 255);
            }

            return (int) round((float) $channel);
        };

        $a = max(0.0, min(1.0, $a));
        $blend = static fn (int $c): int => (int) round($c * $a + 255 * (1 - $a));

        return sprintf(
            'rgb(%d, %d, %d)',
            $blend($to($r)),
            $blend($to($g)),
            $blend($to($b)),
        );
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hslToRgb(float $h, float $s, float $l): array
    {
        $s /= 100;
        $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0.0],
            $h < 120 => [$x, $c, 0.0],
            $h < 180 => [0.0, $c, $x],
            $h < 240 => [0.0, $x, $c],
            $h < 300 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }
}
