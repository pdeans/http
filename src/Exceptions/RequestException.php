<?php

namespace pdeans\Http\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;

/**
 * Request Exception
 *
 * Failed http request exception class.
 */
class RequestException extends TransferException
{
    /**
     * Request object
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    private $request;

    /**
     * Create request exception object.
     */
    public function __construct(string $message, RequestInterface $request, ?Exception $last_exception = null)
    {
        $this->request = $request;

        // \TransferException => \RuntimeException
        parent::__construct($message, 0, $last_exception);
    }

    /**
     * Get the request object.
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
