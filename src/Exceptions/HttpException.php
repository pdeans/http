<?php

namespace pdeans\Http\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Http Exception
 *
 * Failed http exception class when response is received.
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
     * Create HttpException class instance.
     */
    public function __construct(
        string $message,
        RequestInterface $request,
        ResponseInterface $response,
        Exception|null $last_exception = null
    ) {
        parent::__construct($message, $request, $last_exception);

        $this->response = $response;
        $this->code     = $response->getStatusCode();
    }

    /**
     * Get the response object.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Create a new exception with standardized error message.
     */
    public static function create(
        RequestInterface $request,
        ResponseInterface $response,
        Exception|null $last_exception = null
    ): self {
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
