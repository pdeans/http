<?php

namespace pdeans\Http\Contracts;

/**
 * Stream Factory Interface
 *
 * Interface for creating a PSR-7 Stream
 */
interface StreamFactoryInterface
{
	/**
	 * Create a PSR-7 Stream object
	 *
	 * @param \Psr\Http\Message\StreamInterface|resource|string|null  $body  Stream body
	 * @return \Psr\Http\Message\StreamInterface
	 * @throws \InvalidArgumentException  Invalid stream body
	 * @throws \RuntimeException  Failure to create stream body
	 */
	public function createStream($body = null);
}