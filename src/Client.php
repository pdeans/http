<?php

namespace pdeans\Http;

use InvalidArgumentException;
use pdeans\Http\Builders\ResponseBuilder;
use pdeans\Http\Contracts\ExceptionInterface;
use pdeans\Http\Contracts\ClientInterface;
use pdeans\Http\Contracts\MessageFactoryInterface;
use pdeans\Http\Contracts\StreamFactoryInterface;
use pdeans\Http\Exceptions\NetworkException;
use pdeans\Http\Exceptions\RequestException;
use pdeans\Http\Factories\MessageFactory;
use pdeans\Http\Factories\StreamFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use UnexpectedValueException;

/**
 * Client
 *
 * PSR-7 based cURL client
 */
class Client implements ClientInterface
{
	/**
	 * PSR-7 message object
	 *
	 * @var \pdeans\Http\Contracts\MessageFactoryInterface
	 */
	protected $message;

	/**
	 * PSR-7 stream object
	 *
	 * @var \pdeans\Http\Contracts\StreamFactoryInterface
	 */
	protected $stream;

	/**
	 * cURL handler
	 *
	 * @var resource
	 */
	protected $ch;

	/**
	 * cURL options array
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Maximum request body size
	 *
	 * @var int
	 */
	protected static $MAX_BODY_SIZE;

	/**
	 * Create new cURL http client object
	 *
	 * @param \pdeans\Http\Contracts\MessageFactoryInterface|null  $message  Http message object
	 * @param \pdeans\Http\Contracts\StreamFactoryInterface|null  $stream  Http stream object
	 * @param array  $options  cURL options | @link http://php.net/curl_setopt
	 */
	public function __construct(array $options = [], MessageFactoryInterface $message = null, StreamFactoryInterface $stream = null)
	{
		$this->message = $message ?: new MessageFactory;
		$this->stream  = $stream ?: new StreamFactory;
		$this->options = $options;

		self::$MAX_BODY_SIZE = 1024 * 1024;
	}

	/**
	 * Close cURL handler
	 */
	public function __destruct()
	{
		if (is_resource($this->ch)) {
			curl_close($this->ch);
		}
	}

	#---------------------------------------------------#
	# Convenience Request Methods
	#---------------------------------------------------#

	/**
	 * Send a DELETE request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 * @param \Psr\Http\Message\StreamInterface  $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function delete($uri, array $headers = [], $body = null)
	{
		return $this->sendRequest(
			$this->message->createRequest('DELETE', $uri, $headers, $body)
		);
	}

	/**
	 * Send a GET request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function get($uri, array $headers = [])
	{
		return $this->sendRequest(
			$this->message->createRequest('GET', $uri, $headers, null)
		);
	}

	/**
	 * Send a HEAD request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function head($uri, array $headers = [])
	{
		return $this->sendRequest(
			$this->message->createRequest('HEAD', $uri, $headers, null)
		);
	}

	/**
	 * Send an OPTIONS request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 * @param \Psr\Http\Message\StreamInterface  $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function options($uri, array $headers = [], $body = null)
	{
		return $this->sendRequest(
			$this->message->createRequest('OPTIONS', $uri, $headers, $body)
		);
	}

	/**
	 * Send a PATCH request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 * @param \Psr\Http\Message\StreamInterface  $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function patch($uri, array $headers = [], $body = null)
	{
		return $this->sendRequest(
			$this->message->createRequest('PATCH', $uri, $headers, $body)
		);
	}

	/**
	 * Send a POST request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 * @param \Psr\Http\Message\StreamInterface  $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function post($uri, array $headers = [], $body = null)
	{
		return $this->sendRequest(
			$this->message->createRequest('POST', $uri, $headers, $body)
		);
	}

	/**
	 * Send a PUT request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 * @param \Psr\Http\Message\StreamInterface  $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function put($uri, array $headers = [], $body = null)
	{
		return $this->sendRequest(
			$this->message->createRequest('PUT', $uri, $headers, $body)
		);
	}

	/**
	 * Send a TRACE request
	 *
	 * @param \Psr\Http\Message\UriInterface|string  $uri
	 * @param array  $headers
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function trace($uri, array $headers = [])
	{
		return $this->sendRequest(
			$this->message->createRequest('TRACE', $uri, $headers, null)
		);
	}

	#---------------------------------------------------#
	# Send Http Request
	#---------------------------------------------------#

	/**
	 * Send a PSR-7 Request
	 *
	 * @param \Psr\Http\Message\RequestInterface  $request
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws \pdeans\Http\Exceptions\NetworkException  Invalid request due to network issue
	 * @throws \pdeans\Http\Exceptions\RequestException  Invalid request
	 * @throws \InvalidArgumentException  Invalid header names and/or values
	 * @throws \RuntimeException  Failure to create stream
	 */
	public function sendRequest(RequestInterface $request)
	{
		$response = $this->createResponse();
		$options  = $this->createOptions($request, $response);

		// Reset cURL handler
		if (is_resource($this->ch)) {
			if (function_exists('curl_reset')) {
				curl_reset($this->ch);
			}
			else {
				curl_close($this->ch);
				$this->ch = curl_init();
			}
		}
		else {
			$this->ch = curl_init();
		}

		// Setup the cURL request
		curl_setopt_array($this->ch, $options);

		// Execute the request
		curl_exec($this->ch);

		// Check for any request errors
		switch (curl_errno($this->ch)) {
			case CURLE_OK:
				break;
			case CURLE_COULDNT_RESOLVE_PROXY:
			case CURLE_COULDNT_RESOLVE_HOST:
			case CURLE_COULDNT_CONNECT:
			case CURLE_OPERATION_TIMEOUTED:
			case CURLE_SSL_CONNECT_ERROR:
				throw new NetworkException(curl_error($this->ch), $request);
			default:
				throw new RequestException(curl_error($this->ch), $request);
		}

		// Get the response
		$response = $response->getResponse();

		// Seek to the beginning of the request body
		$response->getBody()->seek(0);

		return $response;
	}

	#---------------------------------------------------#
	# Http Response
	#---------------------------------------------------#

	/**
	 * Create a new http response
	 *
	 * @return \pdeans\Http\Builders\ResponseBuilder
	 *
	 * @throws \RuntimeException  Failure to create stream
	 */
	protected function createResponse()
	{
		try {
			$body = $this->stream->createStream(fopen('php://temp', 'w+b'));
		}
		catch (InvalidArgumentException $e) {
			throw new RuntimeException('Unable to create stream "php://temp"');
		}

		return new ResponseBuilder(
			$this->message->createResponse(200, null, [], $body)
		);
	}

	#---------------------------------------------------#
	# Request Headers
	#---------------------------------------------------#

	/**
	 * Create array of headers to pass to CURLOPT_HTTPHEADER
	 *
	 * @param \Psr\Http\Message\RequestInterface  $request  Request object
	 * @param array  $options  cURL options
	 * @return array  Array of http header lines
	 */
	protected function createHeaders(RequestInterface $request, array $options)
	{
		$headers         = [];
		$request_headers = $request->getHeaders();

		foreach ($request_headers as $name => $values) {
			$header = strtoupper($name);

			// cURL does not support 'Expect-Continue', skip all 'EXPECT' headers
			if ($header === 'EXPECT') {
				continue;
			}

			if ($header === 'CONTENT-LENGTH') {
				if (array_key_exists(CURLOPT_POSTFIELDS, $options)) {
					$values = [strlen($options[CURLOPT_POSTFIELDS])];
				}
				// Force content length to '0' if body is empty
				else if (!array_key_exists(CURLOPT_READFUNCTION, $options)) {
					$values = [0];
				}
			}

			foreach ($values as $value) {
				$headers[] = $name.': '.$value;
			}
		}

		// Although cURL does not support 'Expect-Continue', it adds the 'Expect'
		// header by default, so we need to force 'Expect' to empty.
		$headers[] = 'Expect:';

		return $headers;
	}

	#---------------------------------------------------#
	# cURL Options
	#---------------------------------------------------#

	/**
	 * Create cURL request options
	 *
	 * @param \Psr\Http\Message\RequestInterface  $request
	 * @param \pdeans\Http\Builders\ResponseBuilder  $response
	 * @return array  cURL options
	 *
	 * @throws \pdeans\Http\Exceptions\RequestException  Invalid request
	 * @throws \InvalidArgumentException  Invalid header names and/or values
	 * @throws \RuntimeException  Unable to read request body
	 */
	protected function createOptions(RequestInterface $request, ResponseBuilder $response)
	{
		$options = $this->options;

		// These options default to false and cannot be changed on set up.
		// The options should be provided with the request instead.
		$options[CURLOPT_FOLLOWLOCATION] = false;
		$options[CURLOPT_HEADER]         = false;
		$options[CURLOPT_RETURNTRANSFER] = false;

		try {
			$options[CURLOPT_HTTP_VERSION] = $this->getProtocolVersion($request->getProtocolVersion());
		}
		catch (UnexpectedValueException $e) {
			throw new RequestException($e->getMessage(), $request);
		}

		$options[CURLOPT_URL] = (string)$request->getUri();

		$options = $this->addRequestBodyOptions($request, $options);

		$options[CURLOPT_HTTPHEADER] = $this->createHeaders($request, $options);

		if ($request->getUri()->getUserInfo()) {
			$options[CURLOPT_USERPWD] = $request->getUri()->getUserInfo();
		}

		$options[CURLOPT_HEADERFUNCTION] = function ($ch, $data) use ($response) {
			$clean_data = trim($data);

			if ($clean_data !== '') {
				if (strpos(strtoupper($clean_data), 'HTTP/') === 0) {
					$response->setStatus($clean_data)->getResponse();
				}
				else {
					$response->addHeader($clean_data);
				}
			}

			return strlen($data);
		};

		$options[CURLOPT_WRITEFUNCTION] = function ($ch, $data) use ($response) {
			return $response->getResponse()->getBody()->write($data);
		};

		return $options;
	}

	/**
	 * Add cURL options related to the request body
	 *
	 * @param \Psr\Http\Message\RequestInterface  $request  Request object
	 * @param array  $options  cURL options
	 */
	protected function addRequestBodyOptions(RequestInterface $request, array $options)
	{
		/*
		 * HTTP methods that cannot have payload:
		 * - GET   => cURL will automatically change method to PUT or POST if we
		 *            set CURLOPT_UPLOAD or CURLOPT_POSTFIELDS.
		 * - HEAD  => cURL treats HEAD as GET request with a same restrictions.
		 * - TRACE => According to RFC7231: a client MUST NOT send a message body
		 *            in a TRACE request.
		 */
		$http_methods = [
			'GET',
			'HEAD',
			'TRACE',
		];

		if (!in_array($request->getMethod(), $http_methods, true)) {
			$body      = $request->getBody();
			$body_size = $body->getSize();

			if ($body_size !== 0) {
				if ($body->isSeekable()) {
					$body->rewind();
				}

				if ($body_size === null || $body_size > self::$MAX_BODY_SIZE) {
					$options[CURLOPT_UPLOAD] = true;

					if ($body_size !== null) {
						$options[CURLOPT_INFILESIZE] = $body_size;
					}

					$options[CURLOPT_READFUNCTION] = function ($ch, $fd, $len) use ($body) {
						return $body->read($len);
					};
				}
				else {
					$options[CURLOPT_POSTFIELDS] = (string)$body;
				}
			}
		}

		if ($request->getMethod() === 'HEAD') {
			$options[CURLOPT_NOBODY] = true;
		}
		else if ($request->getMethod() !== 'GET') {
			$options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
		}

		return $options;
	}

	/**
	 * Get cURL constant for request http protocol version
	 *
	 * @param string  $request_protocol_version  Request http protocol version
	 * @return int  cURL constant for request http protocol version
	 *
	 * @throws \UnexpectedValueException  Unsupported cURL http protocol version
	 */
	protected function getProtocolVersion($request_protocol_version)
	{
		switch ($request_protocol_version) {
			case '1.0':
				return CURL_HTTP_VERSION_1_0;
			case '1.1':
				return CURL_HTTP_VERSION_1_1;
			case '2.0':
				if (defined('CURL_HTTP_VERSION_2_0')) {
					return CURL_HTTP_VERSION_2_0;
				}

				throw new UnexpectedValueException('libcurl 7.33 required for HTTP 2.0');
		}

		return CURL_HTTP_VERSION_NONE;
	}
}