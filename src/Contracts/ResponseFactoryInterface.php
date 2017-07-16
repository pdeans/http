<?php

namespace pdeans\Http\Contracts;

/**
 * Response Factory Interface
 *
 * Interface for creating a PSR-7 Response
 */
interface ResponseFactoryInterface
{
	/**
	 * Create a PSR-7 response
	 *
	 * @param integer $status  Response status code
	 * @param [type]  $reason  Response reason phrase
	 * @param array   $headers  Response headers
	 * @param [type]  $body  Response body
	 * @param string  $protocol  Response http protocol version
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function createResponse($status = 200, $reason = null, array $headers = [], $body = null, $protocol_version = '1.1');
}