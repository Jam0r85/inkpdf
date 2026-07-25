<?php

declare(strict_types=1);

namespace InkPdf\Css;

/**
 * Print-safe CSS designed around what mPDF actually renders well.
 *
 * Tables + box model + typography — not flex/grid.
 * Utilities mirror common Tailwind class names so document templates can
 * reuse familiar markup. Inject via InkPdf::withDocumentStyles().
 */
final class DocumentStylesheet
{
    /**
     * Tailwind-like spacing scale (px). Matches the default 4px step.
     *
     * @return array<int|string, string>
     */
    private static function spacingScale(): array
    {
        return [
            0 => '0',
            0.5 => '2px',
            1 => '4px',
            1.5 => '6px',
            2 => '8px',
            2.5 => '10px',
            3 => '12px',
            3.5 => '14px',
            4 => '16px',
            5 => '20px',
            6 => '24px',
            7 => '28px',
            8 => '32px',
            9 => '36px',
            10 => '40px',
            11 => '44px',
            12 => '48px',
            14 => '56px',
            16 => '64px',
            20 => '80px',
            24 => '96px',
        ];
    }

    /**
     * Tailwind default palette (subset of shades used in documents).
     *
     * @return array<string, array<int, string>>
     */
    private static function palette(): array
    {
        return [
            'slate' => [
                50 => '#f8fafc', 100 => '#f1f5f9', 200 => '#e2e8f0', 300 => '#cbd5e1',
                400 => '#94a3b8', 500 => '#64748b', 600 => '#475569', 700 => '#334155',
                800 => '#1e293b', 900 => '#0f172a', 950 => '#020617',
            ],
            'gray' => [
                50 => '#f9fafb', 100 => '#f3f4f6', 200 => '#e5e7eb', 300 => '#d1d5db',
                400 => '#9ca3af', 500 => '#6b7280', 600 => '#4b5563', 700 => '#374151',
                800 => '#1f2937', 900 => '#111827', 950 => '#030712',
            ],
            'zinc' => [
                50 => '#fafafa', 100 => '#f4f4f5', 200 => '#e4e4e7', 300 => '#d4d4d8',
                400 => '#a1a1aa', 500 => '#71717a', 600 => '#52525b', 700 => '#3f3f46',
                800 => '#27272a', 900 => '#18181b', 950 => '#09090b',
            ],
            'neutral' => [
                50 => '#fafafa', 100 => '#f5f5f5', 200 => '#e5e5e5', 300 => '#d4d4d4',
                400 => '#a3a3a3', 500 => '#737373', 600 => '#525252', 700 => '#404040',
                800 => '#262626', 900 => '#171717', 950 => '#0a0a0a',
            ],
            'stone' => [
                50 => '#fafaf9', 100 => '#f5f5f4', 200 => '#e7e5e4', 300 => '#d6d3d1',
                400 => '#a8a29e', 500 => '#78716c', 600 => '#57534e', 700 => '#44403c',
                800 => '#292524', 900 => '#1c1917', 950 => '#0c0a09',
            ],
            'red' => [
                50 => '#fef2f2', 100 => '#fee2e2', 200 => '#fecaca', 300 => '#fca5a5',
                400 => '#f87171', 500 => '#ef4444', 600 => '#dc2626', 700 => '#b91c1c',
                800 => '#991b1b', 900 => '#7f1d1d', 950 => '#450a0a',
            ],
            'orange' => [
                50 => '#fff7ed', 100 => '#ffedd5', 200 => '#fed7aa', 300 => '#fdba74',
                400 => '#fb923c', 500 => '#f97316', 600 => '#ea580c', 700 => '#c2410c',
                800 => '#9a3412', 900 => '#7c2d12', 950 => '#431407',
            ],
            'amber' => [
                50 => '#fffbeb', 100 => '#fef3c7', 200 => '#fde68a', 300 => '#fcd34d',
                400 => '#fbbf24', 500 => '#f59e0b', 600 => '#d97706', 700 => '#b45309',
                800 => '#92400e', 900 => '#78350f', 950 => '#451a03',
            ],
            'yellow' => [
                50 => '#fefce8', 100 => '#fef9c3', 200 => '#fef08a', 300 => '#fde047',
                400 => '#facc15', 500 => '#eab308', 600 => '#ca8a04', 700 => '#a16207',
                800 => '#854d0e', 900 => '#713f12', 950 => '#422006',
            ],
            'lime' => [
                50 => '#f7fee7', 100 => '#ecfccb', 200 => '#d9f99d', 300 => '#bef264',
                400 => '#a3e635', 500 => '#84cc16', 600 => '#65a30d', 700 => '#4d7c0f',
                800 => '#3f6212', 900 => '#365314', 950 => '#1a2e05',
            ],
            'green' => [
                50 => '#f0fdf4', 100 => '#dcfce7', 200 => '#bbf7d0', 300 => '#86efac',
                400 => '#4ade80', 500 => '#22c55e', 600 => '#16a34a', 700 => '#15803d',
                800 => '#166534', 900 => '#14532d', 950 => '#052e16',
            ],
            'emerald' => [
                50 => '#ecfdf5', 100 => '#d1fae5', 200 => '#a7f3d0', 300 => '#6ee7b7',
                400 => '#34d399', 500 => '#10b981', 600 => '#059669', 700 => '#047857',
                800 => '#065f46', 900 => '#064e3b', 950 => '#022c22',
            ],
            'teal' => [
                50 => '#f0fdfa', 100 => '#ccfbf1', 200 => '#99f6e4', 300 => '#5eead4',
                400 => '#2dd4bf', 500 => '#14b8a6', 600 => '#0d9488', 700 => '#0f766e',
                800 => '#115e59', 900 => '#134e4a', 950 => '#042f2e',
            ],
            'cyan' => [
                50 => '#ecfeff', 100 => '#cffafe', 200 => '#a5f3fc', 300 => '#67e8f9',
                400 => '#22d3ee', 500 => '#06b6d4', 600 => '#0891b2', 700 => '#0e7490',
                800 => '#155e75', 900 => '#164e63', 950 => '#083344',
            ],
            'sky' => [
                50 => '#f0f9ff', 100 => '#e0f2fe', 200 => '#bae6fd', 300 => '#7dd3fc',
                400 => '#38bdf8', 500 => '#0ea5e9', 600 => '#0284c7', 700 => '#0369a1',
                800 => '#075985', 900 => '#0c4a6e', 950 => '#082f49',
            ],
            'blue' => [
                50 => '#eff6ff', 100 => '#dbeafe', 200 => '#bfdbfe', 300 => '#93c5fd',
                400 => '#60a5fa', 500 => '#3b82f6', 600 => '#2563eb', 700 => '#1d4ed8',
                800 => '#1e40af', 900 => '#1e3a8a', 950 => '#172554',
            ],
            'indigo' => [
                50 => '#eef2ff', 100 => '#e0e7ff', 200 => '#c7d2fe', 300 => '#a5b4fc',
                400 => '#818cf8', 500 => '#6366f1', 600 => '#4f46e5', 700 => '#4338ca',
                800 => '#3730a3', 900 => '#312e81', 950 => '#1e1b4b',
            ],
            'violet' => [
                50 => '#f5f3ff', 100 => '#ede9fe', 200 => '#ddd6fe', 300 => '#c4b5fd',
                400 => '#a78bfa', 500 => '#8b5cf6', 600 => '#7c3aed', 700 => '#6d28d9',
                800 => '#5b21b6', 900 => '#4c1d95', 950 => '#2e1065',
            ],
            'purple' => [
                50 => '#faf5ff', 100 => '#f3e8ff', 200 => '#e9d5ff', 300 => '#d8b4fe',
                400 => '#c084fc', 500 => '#a855f7', 600 => '#9333ea', 700 => '#7e22ce',
                800 => '#6b21a8', 900 => '#581c87', 950 => '#3b0764',
            ],
            'fuchsia' => [
                50 => '#fdf4ff', 100 => '#fae8ff', 200 => '#f5d0fe', 300 => '#f0abfc',
                400 => '#e879f9', 500 => '#d946ef', 600 => '#c026d3', 700 => '#a21caf',
                800 => '#86198f', 900 => '#701a75', 950 => '#4a044e',
            ],
            'pink' => [
                50 => '#fdf2f8', 100 => '#fce7f3', 200 => '#fbcfe8', 300 => '#f9a8d4',
                400 => '#f472b6', 500 => '#ec4899', 600 => '#db2777', 700 => '#be185d',
                800 => '#9d174d', 900 => '#831843', 950 => '#500724',
            ],
            'rose' => [
                50 => '#fff1f2', 100 => '#ffe4e6', 200 => '#fecdd3', 300 => '#fda4af',
                400 => '#fb7185', 500 => '#f43f5e', 600 => '#e11d48', 700 => '#be123c',
                800 => '#9f1239', 900 => '#881337', 950 => '#4c0519',
            ],
        ];
    }

    public static function base(): string
    {
        $chunks = [
            self::documentBase(),
            self::typographyUtilities(),
            self::alignmentUtilities(),
            self::displayUtilities(),
            self::spacingUtilities(),
            self::sizingUtilities(),
            self::borderUtilities(),
            self::colourUtilities(),
            self::semanticColourAliases(),
            self::tablePatterns(),
            self::layoutPatterns(),
            self::pageHelpers(),
        ];

        return implode("\n\n", $chunks);
    }

    private static function documentBase(): string
    {
        return <<<'CSS'
/* InkPDF document base — mPDF-friendly (Tailwind-like utilities below) */
* { box-sizing: border-box; }

body {
    font-family: Inter, DejaVu Sans, sans-serif;
    font-size: 10pt;
    line-height: 1.45;
    color: #111827;
    margin: 0;
    padding: 0;
}

p { margin: 0 0 8px 0; }
h1, h2, h3, h4 {
    margin: 0 0 8px 0;
    font-weight: bold;
    color: #0f172a;
    line-height: 1.25;
}
h1 { font-size: 20pt; }
h2 { font-size: 14pt; }
h3 { font-size: 12pt; }
h4 { font-size: 10.5pt; }

a { color: #1d4ed8; text-decoration: none; }

table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
}
th, td {
    vertical-align: top;
    text-align: left;
}

img { max-width: 100%; border: 0; }

hr {
    border: 0;
    border-top: 1px solid #e5e7eb;
    margin: 16px 0;
}
CSS;
    }

    private static function typographyUtilities(): string
    {
        $sizes = [
            'xs' => '8pt',
            'sm' => '9pt',
            'base' => '10pt',
            'md' => '11pt', // InkPDF alias (not Tailwind)
            'lg' => '12pt',
            'xl' => '14pt',
            '2xl' => '16pt',
            '3xl' => '20pt',
            '4xl' => '24pt',
            '5xl' => '30pt',
            '6xl' => '36pt',
        ];

        $css = ["/* ---- Typography ---- */"];
        foreach ($sizes as $name => $size) {
            $css[] = ".text-{$name} { font-size: {$size}; }";
        }

        $css[] = '.font-thin { font-weight: 100; }';
        $css[] = '.font-extralight { font-weight: 200; }';
        $css[] = '.font-light { font-weight: 300; }';
        $css[] = '.font-normal { font-weight: normal; }';
        $css[] = '.font-medium { font-weight: 500; }';
        $css[] = '.font-semibold { font-weight: 600; }';
        $css[] = '.font-bold { font-weight: bold; }';
        $css[] = '.font-extrabold { font-weight: 800; }';
        $css[] = '.font-black { font-weight: 900; }';

        $css[] = '.italic { font-style: italic; }';
        $css[] = '.not-italic { font-style: normal; }';

        $css[] = '.uppercase { text-transform: uppercase; letter-spacing: 0.04em; }';
        $css[] = '.lowercase { text-transform: lowercase; }';
        $css[] = '.capitalize { text-transform: capitalize; }';
        $css[] = '.normal-case { text-transform: none; letter-spacing: normal; }';

        $css[] = '.underline { text-decoration: underline; }';
        $css[] = '.line-through { text-decoration: line-through; }';
        $css[] = '.no-underline { text-decoration: none; }';

        $css[] = '.leading-none { line-height: 1; }';
        $css[] = '.leading-tight { line-height: 1.2; }';
        $css[] = '.leading-snug { line-height: 1.3; }';
        $css[] = '.leading-normal { line-height: 1.45; }';
        $css[] = '.leading-relaxed { line-height: 1.6; }';
        $css[] = '.leading-loose { line-height: 1.7; }';

        $css[] = '.tracking-tighter { letter-spacing: -0.04em; }';
        $css[] = '.tracking-tight { letter-spacing: -0.02em; }';
        $css[] = '.tracking-normal { letter-spacing: 0; }';
        $css[] = '.tracking-wide { letter-spacing: 0.04em; }';
        $css[] = '.tracking-wider { letter-spacing: 0.08em; }';
        $css[] = '.tracking-widest { letter-spacing: 0.12em; }';

        $css[] = '.whitespace-normal { white-space: normal; }';
        $css[] = '.whitespace-nowrap, .nowrap { white-space: nowrap; }';
        $css[] = '.whitespace-pre { white-space: pre; }';
        $css[] = '.break-words { word-wrap: break-word; overflow-wrap: break-word; }';
        $css[] = '.break-all { word-break: break-all; }';

        return implode("\n", $css);
    }

    private static function alignmentUtilities(): string
    {
        return <<<'CSS'
/* ---- Alignment ---- */
.text-left    { text-align: left; }
.text-center  { text-align: center; }
.text-right   { text-align: right; }
.text-justify { text-align: justify; }
.text-start   { text-align: left; }
.text-end     { text-align: right; }

.align-baseline { vertical-align: baseline; }
.align-top      { vertical-align: top; }
.align-middle   { vertical-align: middle; }
.align-bottom   { vertical-align: bottom; }
.align-text-top { vertical-align: text-top; }
.align-text-bottom { vertical-align: text-bottom; }
CSS;
    }

    private static function displayUtilities(): string
    {
        return <<<'CSS'
/* ---- Display / visibility (no flex/grid) ---- */
.block        { display: block; }
.inline-block { display: inline-block; }
.inline       { display: inline; }
.table        { display: table; width: 100%; }
.hidden       { display: none; }
.invisible    { visibility: hidden; }
.visible      { visibility: visible; }

.overflow-hidden { overflow: hidden; }
.overflow-visible { overflow: visible; }
CSS;
    }

    private static function spacingUtilities(): string
    {
        $scale = self::spacingScale();
        $css = ["/* ---- Spacing (padding / margin) ---- */"];

        foreach ($scale as $step => $value) {
            $name = self::classStep($step);

            $css[] = ".p-{$name} { padding: {$value}; }";
            $css[] = ".px-{$name} { padding-left: {$value}; padding-right: {$value}; }";
            $css[] = ".py-{$name} { padding-top: {$value}; padding-bottom: {$value}; }";
            $css[] = ".pt-{$name} { padding-top: {$value}; }";
            $css[] = ".pr-{$name} { padding-right: {$value}; }";
            $css[] = ".pb-{$name} { padding-bottom: {$value}; }";
            $css[] = ".pl-{$name} { padding-left: {$value}; }";

            $css[] = ".m-{$name} { margin: {$value}; }";
            $css[] = ".mx-{$name} { margin-left: {$value}; margin-right: {$value}; }";
            $css[] = ".my-{$name} { margin-top: {$value}; margin-bottom: {$value}; }";
            $css[] = ".mt-{$name} { margin-top: {$value}; }";
            $css[] = ".mr-{$name} { margin-right: {$value}; }";
            $css[] = ".mb-{$name} { margin-bottom: {$value}; }";
            $css[] = ".ml-{$name} { margin-left: {$value}; }";
        }

        $css[] = '.m-auto { margin: auto; }';
        $css[] = '.mx-auto { margin-left: auto; margin-right: auto; }';
        $css[] = '.my-auto { margin-top: auto; margin-bottom: auto; }';
        $css[] = '.mt-auto { margin-top: auto; }';
        $css[] = '.mr-auto { margin-right: auto; }';
        $css[] = '.mb-auto { margin-bottom: auto; }';
        $css[] = '.ml-auto { margin-left: auto; }';

        // Negative margins (common for pull-up spacing in docs)
        foreach ([1 => '4px', 2 => '8px', 3 => '12px', 4 => '16px', 6 => '24px', 8 => '32px'] as $step => $value) {
            $css[] = ".-mt-{$step} { margin-top: -{$value}; }";
            $css[] = ".-mb-{$step} { margin-bottom: -{$value}; }";
            $css[] = ".-ml-{$step} { margin-left: -{$value}; }";
            $css[] = ".-mr-{$step} { margin-right: -{$value}; }";
        }

        return implode("\n", $css);
    }

    private static function sizingUtilities(): string
    {
        $css = ["/* ---- Width / height ---- */"];

        $css[] = '.w-full { width: 100%; }';
        $css[] = '.w-auto { width: auto; }';
        $css[] = '.w-screen { width: 100%; }';
        $css[] = '.min-w-0 { min-width: 0; }';
        $css[] = '.min-w-full { min-width: 100%; }';
        $css[] = '.max-w-full { max-width: 100%; }';
        $css[] = '.max-w-none { max-width: none; }';

        $css[] = '.h-auto { height: auto; }';
        $css[] = '.h-full { height: 100%; }';
        $css[] = '.min-h-0 { min-height: 0; }';
        $css[] = '.min-h-full { min-height: 100%; }';

        // Fraction widths — Tailwind slash form + legacy hyphen form
        $fractions = [
            '1/2' => '50%',
            '1/3' => '33.333333%',
            '2/3' => '66.666667%',
            '1/4' => '25%',
            '2/4' => '50%',
            '3/4' => '75%',
            '1/5' => '20%',
            '2/5' => '40%',
            '3/5' => '60%',
            '4/5' => '80%',
            '1/6' => '16.666667%',
            '5/6' => '83.333333%',
            '1/12' => '8.333333%',
            '5/12' => '41.666667%',
            '7/12' => '58.333333%',
            '11/12' => '91.666667%',
        ];

        foreach ($fractions as $frac => $width) {
            $slash = str_replace('/', '\\/', $frac);
            $hyphen = str_replace('/', '-', $frac);
            $css[] = ".w-{$slash}, .w-{$hyphen} { width: {$width}; }";
        }

        // Percent widths used in invoices (legacy InkPDF helpers)
        foreach ([10, 15, 20, 25, 30, 33, 35, 40, 45, 50, 55, 60, 65, 66, 70, 75, 80, 85, 90, 95, 100] as $pct) {
            $css[] = ".w-{$pct} { width: {$pct}%; }";
            $css[] = ".w-\\[{$pct}\\%\\] { width: {$pct}%; }"; // Tailwind arbitrary-ish
        }

        // Fixed widths from spacing scale
        foreach (self::spacingScale() as $step => $value) {
            if ($step === 0 || $step === 0.0) {
                continue;
            }
            $name = self::classStep($step);
            $css[] = ".w-{$name} { width: {$value}; }";
            $css[] = ".h-{$name} { height: {$value}; }";
        }

        return implode("\n", $css);
    }

    private static function borderUtilities(): string
    {
        $css = ["/* ---- Borders / radius ---- */"];

        $css[] = '.border { border: 1px solid #e5e7eb; }';
        $css[] = '.border-0 { border: 0; }';
        $css[] = '.border-2 { border-width: 2px; border-style: solid; border-color: #e5e7eb; }';
        $css[] = '.border-4 { border-width: 4px; border-style: solid; border-color: #e5e7eb; }';
        $css[] = '.border-8 { border-width: 8px; border-style: solid; border-color: #e5e7eb; }';

        $css[] = '.border-t { border-top: 1px solid #e5e7eb; }';
        $css[] = '.border-r { border-right: 1px solid #e5e7eb; }';
        $css[] = '.border-b { border-bottom: 1px solid #e5e7eb; }';
        $css[] = '.border-l { border-left: 1px solid #e5e7eb; }';
        $css[] = '.border-x { border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }';
        $css[] = '.border-y { border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }';

        $css[] = '.border-t-0 { border-top: 0; }';
        $css[] = '.border-r-0 { border-right: 0; }';
        $css[] = '.border-b-0 { border-bottom: 0; }';
        $css[] = '.border-l-0 { border-left: 0; }';

        $css[] = '.border-t-2 { border-top: 2px solid #0f172a; }';
        $css[] = '.border-b-2 { border-bottom: 2px solid #0f172a; }';
        $css[] = '.border-l-2 { border-left: 2px solid #0f172a; }';
        $css[] = '.border-r-2 { border-right: 2px solid #0f172a; }';
        $css[] = '.border-t-4 { border-top: 4px solid #0f172a; }';
        $css[] = '.border-b-4 { border-bottom: 4px solid #0f172a; }';

        $css[] = '.border-solid { border-style: solid; }';
        $css[] = '.border-dashed { border-style: dashed; }';
        $css[] = '.border-dotted { border-style: dotted; }';
        $css[] = '.border-none { border-style: none; }';

        $css[] = '.rounded-none { border-radius: 0; }';
        $css[] = '.rounded-sm { border-radius: 2px; }';
        $css[] = '.rounded { border-radius: 4px; }';
        $css[] = '.rounded-md { border-radius: 6px; }';
        $css[] = '.rounded-lg { border-radius: 8px; }';
        $css[] = '.rounded-xl { border-radius: 12px; }';
        $css[] = '.rounded-2xl { border-radius: 16px; }';
        $css[] = '.rounded-full { border-radius: 9999px; }';

        $css[] = '.border-transparent { border-color: transparent; }';
        $css[] = '.border-current { border-color: currentColor; }';
        $css[] = '.border-white { border-color: #ffffff; }';
        $css[] = '.border-black { border-color: #000000; }';
        $css[] = '.border-ink { border-color: #0f172a; }';

        return implode("\n", $css);
    }

    private static function colourUtilities(): string
    {
        $css = ["/* ---- Tailwind colour scale (text / bg / border) ---- */"];

        $css[] = '.text-inherit { color: inherit; }';
        $css[] = '.text-current { color: currentColor; }';
        $css[] = '.text-transparent { color: transparent; }';
        $css[] = '.text-black { color: #000000; }';
        $css[] = '.text-white { color: #ffffff; }';

        $css[] = '.bg-transparent { background-color: transparent; }';
        $css[] = '.bg-current { background-color: currentColor; }';
        $css[] = '.bg-black { background-color: #000000; }';
        $css[] = '.bg-white { background-color: #ffffff; }';

        foreach (self::palette() as $name => $shades) {
            foreach ($shades as $shade => $hex) {
                $css[] = ".text-{$name}-{$shade} { color: {$hex}; }";
                $css[] = ".bg-{$name}-{$shade} { background-color: {$hex}; }";
                $css[] = ".border-{$name}-{$shade} { border-color: {$hex}; }";
            }
            // Default shade (500) without number, e.g. text-blue
            if (isset($shades[500])) {
                $css[] = ".text-{$name} { color: {$shades[500]}; }";
                $css[] = ".bg-{$name} { background-color: {$shades[500]}; }";
                $css[] = ".border-{$name} { border-color: {$shades[500]}; }";
            }
        }

        // Opacity helpers (limited mPDF support — applied as colour still preferred)
        foreach ([0, 5, 10, 20, 25, 30, 40, 50, 60, 70, 75, 80, 90, 95, 100] as $pct) {
            $opacity = number_format($pct / 100, 2, '.', '');
            $css[] = ".opacity-{$pct} { opacity: {$opacity}; }";
        }

        return implode("\n", $css);
    }

    private static function semanticColourAliases(): string
    {
        return <<<'CSS'
/* ---- Semantic colour aliases (InkPDF / document conveniences) ---- */
.text-ink     { color: #0f172a; }
.text-body    { color: #111827; }
.text-muted   { color: #6b7280; }
.text-faint   { color: #9ca3af; }
.text-primary { color: #1d4ed8; }
.text-success { color: #047857; }
.text-warning { color: #b45309; }
.text-danger  { color: #b91c1c; }

.bg-slate   { background-color: #f8fafc; }
.bg-muted   { background-color: #f3f4f6; }
.bg-ink     { background-color: #0f172a; }
.bg-primary { background-color: #1d4ed8; }
.bg-success { background-color: #ecfdf5; }
.bg-warning { background-color: #fffbeb; }
.bg-danger  { background-color: #fef2f2; }
CSS;
    }

    private static function tablePatterns(): string
    {
        return <<<'CSS'
/* ---- Tables: document patterns ---- */
.table-plain th,
.table-plain td {
    padding: 2px 0;
    border: 0;
}

.table-lines th {
    background-color: #0f172a;
    color: #ffffff;
    font-size: 9pt;
    font-weight: bold;
    padding: 8px 6px;
    border: 0;
}
.table-lines td {
    padding: 8px 6px;
    border-bottom: 1px solid #e5e7eb;
}
.table-lines .num,
.num {
    text-align: right;
    white-space: nowrap;
}

.table-zebra tbody tr:nth-child(even) td {
    background-color: #f8fafc;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #e5e7eb;
    padding: 8px 6px;
}

.table-compact th,
.table-compact td {
    padding: 4px 6px;
    font-size: 9pt;
}

/* Totals column block (right-aligned table) */
.totals {
    width: 45%;
    margin-left: auto;
    margin-top: 12px;
}
.totals td { padding: 4px 6px; }
.totals .grand td {
    border-top: 2px solid #0f172a;
    font-weight: bold;
    font-size: 11pt;
    padding-top: 8px;
}
CSS;
    }

    private static function layoutPatterns(): string
    {
        return <<<'CSS'
/* ---- Layout blocks ---- */
.panel {
    border: 1px solid #e5e7eb;
    background-color: #f8fafc;
    padding: 12px 14px;
}
.panel-ink {
    background-color: #0f172a;
    color: #ffffff;
    border: 0;
    padding: 12px 14px;
}

.badge {
    display: inline-block;
    font-size: 8.5pt;
    font-weight: bold;
    padding: 3px 8px;
    border: 1px solid #e5e7eb;
    background-color: #f3f4f6;
    color: #374151;
}
.badge-success {
    background-color: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.badge-warning {
    background-color: #fffbeb;
    color: #b45309;
    border-color: #fcd34d;
}
.badge-danger {
    background-color: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.badge-primary {
    background-color: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.label {
    font-size: 8pt;
    font-weight: bold;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.footer-note {
    margin-top: 28px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
    font-size: 8.5pt;
    color: #6b7280;
}

/* Two-column via table (flex alternative) */
.cols { width: 100%; border-collapse: collapse; }
.cols > tbody > tr > td { vertical-align: top; padding: 0; }
.cols-gap > tbody > tr > td { padding-right: 16px; }
.cols-gap > tbody > tr > td:last-child { padding-right: 0; padding-left: 16px; }

/* Stack of spaced rows without flex (margin on children) */
.stack > * { display: block; margin-bottom: 8px; }
.stack > *:last-child { margin-bottom: 0; }
.stack-sm > * { display: block; margin-bottom: 4px; }
.stack-sm > *:last-child { margin-bottom: 0; }
.stack-lg > * { display: block; margin-bottom: 16px; }
.stack-lg > *:last-child { margin-bottom: 0; }
CSS;
    }

    private static function pageHelpers(): string
    {
        return <<<'CSS'
/* ---- Page helpers (mPDF) ---- */
.page-break, .break-before { page-break-before: always; }
.break-after  { page-break-after: always; }
.avoid-break, .break-inside-avoid, .keep-together { page-break-inside: avoid; }
CSS;
    }

    /**
     * CSS class step segment with dots escaped: 0.5 → "0\.5", 1 → "1".
     * HTML stays `class="p-0.5"`; CSS needs `.p-0\.5`.
     */
    private static function classStep(int|float|string $step): string
    {
        if (is_int($step)) {
            return (string) $step;
        }

        if (is_float($step)) {
            $formatted = rtrim(rtrim(sprintf('%.1f', $step), '0'), '.');
            $formatted = $formatted === '' ? '0' : $formatted;

            return str_replace('.', '\\.', $formatted);
        }

        return str_replace('.', '\\.', (string) $step);
    }

    /**
     * Optional brand overrides — pass hex colours without #.
     *
     * @param  array{ink?: string, primary?: string, success?: string, muted?: string, border?: string}  $brand
     */
    public static function brand(array $brand = []): string
    {
        $ink = self::hex($brand['ink'] ?? '0f172a');
        $primary = self::hex($brand['primary'] ?? '1d4ed8');
        $success = self::hex($brand['success'] ?? '047857');
        $muted = self::hex($brand['muted'] ?? '6b7280');
        $border = self::hex($brand['border'] ?? 'e5e7eb');

        return <<<CSS
/* Brand overrides */
h1, h2, h3, h4, .text-ink { color: {$ink}; }
.text-primary, a { color: {$primary}; }
.text-success { color: {$success}; }
.text-muted, .label, .footer-note { color: {$muted}; }
.bg-ink, .table-lines th { background-color: {$ink}; }
.bg-primary { background-color: {$primary}; }
.border, .border-t, .border-b, .border-l, .border-r, .border-x, .border-y, hr, .panel, .footer-note {
    border-color: {$border};
}
.border-t-2, .border-b-2, .border-l-2, .border-r-2, .border-t-4, .border-b-4, .totals .grand td {
    border-color: {$ink};
}
CSS;
    }

    private static function hex(string $value): string
    {
        $value = ltrim(trim($value), '#');
        if (! preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
            throw new \InvalidArgumentException("Invalid hex colour: {$value}");
        }

        return '#' . strtolower($value);
    }
}
