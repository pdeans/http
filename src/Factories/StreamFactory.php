<?php

namespace pdeans\Http\Factories;

use pdeans\Http\Contracts\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Stream;

/**
 * Stream Factory
 *
 * Factory for creating Zend\Diactoros PSR-7 Streams
 */
final class StreamFactory implements StreamFactoryInterface
{
	/**
	 * Create a Zend\Diactoros PSR-7 Stream
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

		$stream = new Stream(fopen('php://memory', 'rw'));

		if ($body !== null && $body !== '') {
			$stream->write((string)$body);
		}

		return $stream;
	}
}