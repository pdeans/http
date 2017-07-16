<?php

namespace pdeans\Http\Contracts;

use Psr\Http\Message\RequestInterface;

/**
 * Client Interface
 *
 * Interface for handling http requests
 */
interface ClientInterface
{
	/**
	 * Send a PSR-7 http request
	 *
	 * This will act as the underlying http client
	 * @link http://docs.php-http.org/en/latest/httplug/introduction.html
	 *
	 * @param \Psr\Http\Message\RequestInterface $request
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws \pdeans\Http\Contracts\ExceptionInterface  If an error happens during processing the request.
	 * @throws \Exception  If processing the request fails.
	 */
	public function sendRequest(RequestInterface $request);
}