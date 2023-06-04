<?php

namespace pdeans\Http\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Client Interface
 *
 * Interface for handling http requests
 */
interface ClientInterface
{
    /**
     * Send a PSR-7 http request.
     *
     * @throws \pdeans\Http\Contracts\ExceptionInterface  If an error happens during processing the request.
     * @throws \Exception  If processing the request fails.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
