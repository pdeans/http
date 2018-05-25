<?php

namespace pdeans\Http\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Http Exception
 *
 * Failed http exception class when response is receieved
 */
class HttpException extends RequestException
{
	/**
	 * Response object
	 *
	 * @var \Psr\Http\Message\ResponseInterface
	 */
	protected $response;

	/**
	 * Create HttpException object
	 *
	 * @param string  $message  Exception message
	 * @param \Psr\Http\Message\RequestInterface  $request  Request object
	 * @param \Psr\Http\Message\ResponseInterface  $response  Response object
	 * @param \Exception|null  $last_exception  Previous exception object
	 */
	public function __construct(
		$message,
		RequestInterface $request,
		ResponseInterface $response,
		Exception $last_exception = null
	) {
		parent::__construct($message, $request, $previous);

		$this->response = $response;
		$this->code     = $response->getStatusCode();
	}

	/**
	 * Get the response object
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Create a new exception with standardized error message
	 *
	 * @param \Psr\Http\Message\RequestInterface  $request  Request object
	 * @param \Psr\Http\Message\ResponseInterface  $response  Response object
	 * @param \Exception|null  $last_exception  Previous exception object
	 * @return \pdeans\Http\Exceptions\HttpException
	 */
	public static function create(
		RequestInterface $request,
		ResponseInterface $response,
		Exception $last_exception = null
	) {
		$message = sprintf(
			'[url] %s [http method] %s [status code] %s [reason phrase] %s',
			$request->getRequestTarget(),
			$request->getMethod(),
			$response->getStatusCode(),
			$response->getReasonPhrase()
		);

		return new self($message, $request, $response, $last_exception);
	}
}