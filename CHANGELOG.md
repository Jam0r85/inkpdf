# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] — 2026-07-25

First stable release. Public API: `InkPdf::loadHtml` / `loadFile` / `view`, fluent
`PdfDocument` configuration, optional Laravel service provider, and mPDF as the
default renderer.

### Added
- Optional **Laravel bridge**: auto-discovered `InkPdfServiceProvider`, publishable
  `config/inkpdf.php`, and `InkPdf::view()` for Blade templates.
- Configurable **header / footer margins** for fixed mPDF page footers.
- **Max pages** guard (`max_pages`) to abort runaway layout loops.
- **Debug HTML dumps** on render failure when debug mode is enabled.
- Expanded **Tailwind-compatible document utilities** (spacing, colours, type,
  borders, widths) via `withDocumentStyles()`.
- Safer image handling and default typeface **11pt Inter**.

### Fixed
- mPDF `packTableData` PHP 8 array-offset crash on complex tables.
- Table shrink / keep-with-table loops that could balloon page counts.

### Changed
- Default body / utility type scale aligned to **11pt**.
- Sample templates and docs use generic product naming (no app-specific branding).

## [0.2.1] — prior

- Bundled **Inter** fonts (SIL OFL) as the default typeface.

## [0.2.0] — prior

- Document utilities, CSS normalizer, brand styles.

## [0.1.0] — prior

- Initial release: HTML → PDF via mPDF, fluent builder, examples and tests.

[1.0.0]: https://github.com/Jam0r85/inkpdf/releases/tag/v1.0.0
