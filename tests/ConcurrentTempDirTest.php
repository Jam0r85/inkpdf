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
                $this->assertSame([], glob($dir.'/pid-*') ?: [], 'A finished process left its cache folder behind.');
                $this->removeDir($dir);
            }
        } finally {
            @unlink($file);
        }
    }

    public function test_a_folder_left_by_a_killed_process_is_removed_by_the_next_render(): void
    {
        if (! function_exists('posix_kill')) {
            $this->markTestSkipped('Needs ext-posix.');
        }

        // A process that has ended: its pid is free (for long enough to run this).
        $dead = proc_open([PHP_BINARY, '-r', 'exit(0);'], [], $pipes);
        $deadPid = proc_get_status($dead)['pid'];
        proc_close($dead);

        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'inkpdf-stale-'.bin2hex(random_bytes(4));
        mkdir($dir.'/pid-'.$deadPid.'/mpdf/ttfontdata', 0775, true);
        file_put_contents($dir.'/pid-'.$deadPid.'/mpdf/ttfontdata/x.dat', 'x');
        mkdir($dir.'/pid-'.getmypid().'-not-ours', 0775, true);

        try {
            $pdf = \InkPdf\InkPdf::loadHtml('<p>x</p>')->setTempDir($dir)->output();

            $this->assertStringStartsWith('%PDF', $pdf);
            $this->assertDirectoryDoesNotExist($dir.'/pid-'.$deadPid);
            $this->assertDirectoryExists($dir.'/pid-'.getmypid(), 'A running process keeps its folder.');
        } finally {
            $this->removeDir($dir);
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
