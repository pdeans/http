<?php

namespace pdeans\Http\Contracts;

use Psr\Http\Message\RequestInterface;

/**
 * Request Factory Interface
 *
 * Interface for creating a PSR-7 Request
 */
interface RequestFactoryInterface
{
    /**
     * Create a PSR-7 request
     *
     * @param string  $method
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param array  $headers
     * @param \Psr\Http\Message\StreamInterface|resource|string|null  $body
     * @param string  $protocol_version
     * @return \Psr\Http\Message\RequestInterface
     */
    public function createRequest(
        UriInterface|string $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocol_version = '1.1'
    ): RequestInterface;
}
