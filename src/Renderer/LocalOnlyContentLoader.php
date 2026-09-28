<?php

declare(strict_types=1);

namespace InkPdf\Renderer;

use Mpdf\File\LocalContentLoaderInterface;

/**
 * mPDF's file reader for InkPDF: data URIs and plain local paths only.
 *
 * The HTML being rendered often carries text someone else typed (a name, an address, an email),
 * so anything in it that makes mPDF read a resource must not reach further than the files the
 * app itself passes. `file://`, `phar://`, `//host` and every other URL are refused here, and
 * {@see NoRemoteHttpClient} refuses anything mPDF treats as remote, so `<link href>`, CSS
 * `url()` / `@import` and `<img src>` never fetch from the network or odd stream wrappers.
 */
final class LocalOnlyContentLoader implements LocalContentLoaderInterface
{
    public function load($path)
    {
        $path = trim((string) $path);

        if (self::isDataUri($path)) {
            $contents = @file_get_contents($path);

            return is_string($contents) ? $contents : '';
        }

        if (! self::isLocalPath($path) || ! is_file($path) || ! is_readable($path)) {
            return '';
        }

        $contents = file_get_contents($path);

        return is_string($contents) ? $contents : '';
    }

    public static function isDataUri(string $path): bool
    {
        return strncasecmp(ltrim($path), 'data:', 5) === 0;
    }

    /**
     * A filesystem path: no URL scheme (`http:`, `file:`, `phar:`…) and not protocol-relative
     * (`//host/x`). A Windows drive (`C:\`) counts as a path.
     */
    public static function isLocalPath(string $path): bool
    {
        $path = ltrim($path);

        if ($path === '' || str_starts_with($path, '//') || str_starts_with($path, '\\\\')) {
            return false;
        }

        if (preg_match('#^[a-z]:[\\\\/]#i', $path) === 1) {
            return true;
        }

        return preg_match('#^[a-z][a-z0-9+.\-]*:#i', $path) !== 1;
    }
}
