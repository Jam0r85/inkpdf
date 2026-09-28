<?php

declare(strict_types=1);

namespace InkPdf\Tests;

use InkPdf\Exceptions\RenderException;
use InkPdf\InkPdf;
use InkPdf\Renderer\LocalOnlyContentLoader;
use PHPUnit\Framework\TestCase;

/**
 * The HTML being rendered often carries text someone else typed, so nothing in it may make the
 * renderer fetch a resource (SSRF): only data URIs and local paths the app passes are read.
 */
final class RemoteResourcesTest extends TestCase
{
    /** @var resource|null */
    private $listener = null;

    private string $origin = '';

    protected function setUp(): void
    {
        // A local port that records whether anything connected to it.
        $listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertNotFalse($listener, $error);
        stream_set_blocking($listener, false);
        $this->listener = $listener;
        $this->origin = '127.0.0.1:'.parse_url('tcp://'.stream_socket_get_name($listener, false), PHP_URL_PORT);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->listener)) {
            fclose($this->listener);
        }
    }

    private function somethingConnected(): bool
    {
        return @stream_socket_accept($this->listener, 0) !== false;
    }

    public function test_nothing_in_the_document_is_fetched_from_the_network(): void
    {
        $html = '<link rel="stylesheet" href="http://'.$this->origin.'/style.css">'
            .'<style>@import url("http://'.$this->origin.'/import.css");</style>'
            .'<p>Tenant</p>'
            .'<img src="//'.$this->origin.'/pixel.png" width="10" height="10">'
            .'<img src="http://'.$this->origin.'/pixel.png" width="10" height="10">'
            .'<div style="background-image: url(\'http://'.$this->origin.'/bg.png\'); width: 20mm; height: 20mm;"></div>';

        $pdf = InkPdf::loadHtml($html)->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertFalse($this->somethingConnected(), 'The renderer connected to an address in the document.');
        $this->assertSame(0, preg_match_all('#/Subtype\s*/Image#', $pdf));
    }

    public function test_file_and_other_url_image_sources_are_dropped(): void
    {
        $pdf = InkPdf::loadHtml('<p>x</p><img src="file:///etc/hostname" width="10" height="10"><img src="phar:///tmp/x.phar/a.png" width="10" height="10">')->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(0, preg_match_all('#/Subtype\s*/Image#', $pdf));
    }

    public function test_the_loader_reads_only_data_uris_and_local_paths(): void
    {
        $loader = new LocalOnlyContentLoader();
        $file = tempnam(sys_get_temp_dir(), 'inkpdf-');
        file_put_contents($file, 'local bytes');

        try {
            $this->assertSame('local bytes', $loader->load($file));
            $this->assertSame('hello', $loader->load('data:text/plain;base64,'.base64_encode('hello')));

            foreach (['http://'.$this->origin.'/a', '//'.$this->origin.'/a', 'file://'.$file, 'ftp://'.$this->origin.'/a', 'phar://'.$file.'/a'] as $url) {
                $this->assertSame('', $loader->load($url), $url);
            }
        } finally {
            @unlink($file);
        }

        $this->assertFalse($this->somethingConnected());
    }

    public function test_the_html_is_only_dumped_to_disk_when_debug_is_on(): void
    {
        $dir = sys_get_temp_dir().'/inkpdf-dump-'.bin2hex(random_bytes(4));
        $pages = str_repeat('<p style="page-break-after: always">Page</p>', 3);

        try {
            InkPdf::loadHtml($pages)->setMaxPages(1)->setDebugPath($dir)->output();
            $this->fail('Expected the max-pages guard to stop the render.');
        } catch (RenderException $e) {
            $this->assertStringNotContainsString('HTML dump', $e->getMessage());
        }

        $this->assertSame([], is_dir($dir) ? array_values(array_diff(scandir($dir) ?: [], ['.', '..'])) : []);

        try {
            InkPdf::loadHtml($pages)->setMaxPages(1)->setDebug()->setDebugPath($dir)->output();
            $this->fail('Expected the max-pages guard to stop the render.');
        } catch (RenderException $e) {
            $this->assertStringContainsString('HTML dump', $e->getMessage());
        } finally {
            foreach (glob($dir.'/*') ?: [] as $dump) {
                @unlink($dump);
            }
            @rmdir($dir);
        }
    }
}
