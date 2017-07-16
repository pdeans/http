<?php

namespace pdeans\Http\Contracts;

/**
 * Uri Factory Interface
 *
 * Interface for creating a PSR-7 Uri
 */
interface UriFactoryInterface
{
	/**
	 * Create a PSR-7 Uri object
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri  Request uri
	 * @return \Psr\Http\Message\UriInterface
	 * @throws \InvalidArgumentException  Invalid uri argument passed in
	 */
	public function createUri($uri);
}