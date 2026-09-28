<?php

declare(strict_types=1);

namespace InkPdf\Renderer;

use Mpdf\Http\ClientInterface;
use Mpdf\PsrHttpMessageShim\Response;
use Psr\Http\Message\RequestInterface;

/**
 * mPDF's HTTP client for InkPDF: it never makes a request. {@see LocalOnlyAssetFetcher} already
 * keeps remote URLs away from it; this is the second lock, so no code path in mPDF can reach the
 * network from a rendered document.
 */
final class NoRemoteHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request)
    {
        return new Response(403);
    }
}
