<?php

namespace pdeans\Http\Factories;

use pdeans\Http\Contracts\MessageFactoryInterface;
use pdeans\Http\Factories\StreamFactory;
use pdeans\Http\Factories\UriFactory;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Message Factory
 *
 * Factory for creating Slim PSR-7 Messages
 */
final class MessageFactory implements MessageFactoryInterface
{
	/**
	 * Stream Factory
	 *
	 * @var \pdeans\Http\Factories\StreamFactory
	 */
	private $stream;

	/**
	 * Uri Factory
	 *
	 * @var pdeans\Http\Factories\UriFactory
	 */
	private $uri;

	/**
	 * Create a new Message Factory object
	 */
	public function __construct()
	{
		$this->stream = new StreamFactory;
		$this->uri    = new UriFactory;
	}

	/**
	 * Create a PSR-7 request
	 *
	 * @param string  $method
	 * @param \Psr\Http\Message\UriInterface|string $uri
	 * @param array  $headers
	 * @param \Psr\Http\Message\StreamInterface|resource|string|null  $body
	 * @param string  $protocol_version
	 *
	 * @return \Psr\Http\Message\RequestInterface
	 */
	public function createRequest($method, $uri, array $headers = [], $body = null, $protocol_version = '1.1')
	{
		return (
			new Request(
				$method,
				$this->uri->createUri($uri),
				new Headers($headers),
				[],
				[],
				$this->stream->createStream($body),
				[]
			)
		)->withProtocolVersion($protocol_version);
	}

	/**
	 * Create a PSR-7 response
	 *
	 * @param integer $status  Response status code
	 * @param string|null  $reason  Response reason phrase
	 * @param array   $headers  Response headers
	 * @param \Psr\Http\Message\StreamInterface|resource|string|null  $body  Response body
	 * @param string  $protocol  Response http protocol version
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function createResponse($status = 200, $reason = null, array $headers = [], $body = null, $protocol_version = '1.1')
	{
		return (
			new Response($status, new Headers($headers), $this->stream->createStream($body))
		)->withProtocolVersion($protocol_version);
	}
}