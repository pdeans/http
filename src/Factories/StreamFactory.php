<?php

namespace pdeans\Http\Factories;

use pdeans\Http\Contracts\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Http\Stream;

/**
 * Stream Factory
 *
 * Factory for creating Slim PSR-7 Streams
 */
final class StreamFactory implements StreamFactoryInterface
{
	/**
	 * Create a Slim PSR-7 Stream
	 *
	 * @param \Psr\Http\Message\StreamInterface|resource|string|null  $body  Stream body
	 * @return \Psr\Http\Message\StreamInterface  Stream object
	 */
	public function createStream($body = null)
	{
		if ($body instanceof StreamInterface) {
			return $body;
		}

		if (is_resource($body)) {
			return new Stream($body);
		}

		$stream = new Stream(fopen('php://memory', 'r+'));

		if ($body !== null && $body !== '') {
			$stream->write((string)$body);
		}

		return $stream;
	}
}