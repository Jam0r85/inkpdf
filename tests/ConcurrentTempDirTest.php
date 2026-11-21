<?php

declare(strict_types=1);

namespace InkPdf\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Parallel workers (queue workers, `artisan test --parallel`) share one temp folder. Two of them can
 * both see it missing; the loser's mkdir() warns "File exists", and a framework that turns warnings
 * into exceptions would fail an otherwise fine render.
 */
final class ConcurrentTempDirTest extends TestCase
{
    public function test_renders_racing_to_create_the_temp_folder_all_succeed(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'inkpdf-race-'.bin2hex(random_bytes(4));
        $autoload = dirname(__DIR__).'/vendor/autoload.php';
        $script = <<<'PHP'
<?php
require $argv[1];
set_error_handler(function ($no, $str) { if (! (error_reporting() & $no)) { return false; } throw new ErrorException($str, 0, $no); });
$start = (float) $argv[3];
while (microtime(true) < $start) {}
try {
    $pdf = InkPdf\InkPdf::loadHtml('<p>x</p>')->setTempDir($argv[2])->output();
    exit(str_starts_with($pdf, '%PDF') ? 0 : 2);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n".($e->getPrevious() ?? $e)->getTraceAsString());
    exit(1);
}
PHP;
        $file = $root.'-script.php';
        file_put_contents($file, $script);

        try {
            for ($round = 0; $round < 3; $round++) {
                $dir = $root.'-'.$round;
                $start = microtime(true) + 1.5;
                $procs = [];
                for ($i = 0; $i < 6; $i++) {
                    $procs[] = proc_open([PHP_BINARY, $file, $autoload, $dir, (string) $start], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i]);
                }
                foreach ($procs as $i => $p) {
                    $err = stream_get_contents($pipes[$i][2]);
                    $this->assertSame(0, proc_close($p), "Render failed while racing: {$err}");
                }
                $this->removeDir($dir);
            }
        } finally {
            @unlink($file);
        }
    }

    private function removeDir(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $item) {
            is_dir($item) ? $this->removeDir($item) : @unlink($item);
        }
        @rmdir($dir);
    }
}
