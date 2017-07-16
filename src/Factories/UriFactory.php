<?php

namespace pdeans\Http\Factories;

use InvalidArgumentException;
use pdeans\Http\Contracts\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Http\Uri;

/**
 * Uri Factory
 *
 * Factory for creating Slim PSR-7 Uris
 */
final class UriFactory implements UriFactoryInterface
{
	/**
	 * Create a Slim PSR-7 Uri
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri  Request uri
	 * @return \Psr\Http\Message\UriInterface
	 * @throws \InvalidArgumentException  Invalid uri argument passed in
	 */
	public function createUri($uri)
	{
		if ($uri instanceof UriInterface) {
			return $uri;
		}

		if (is_string($uri)) {
			return Uri::createFromString($uri);
		}

		throw new InvalidArgumentException(
			'Invalid argument. Uri must be a string or instance of \Psr\Http\Message\UriInterface'
		);
	}
}