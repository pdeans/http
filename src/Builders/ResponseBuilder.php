<?php

namespace pdeans\Http\Builders;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * Response Builder
 *
 * Build a PSR-7 Response object
 */
class ResponseBuilder
{
    /**
     * PSR-7 Response
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * Create a Response Builder.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    #-------------------------------------#
    # Response Object
    #-------------------------------------#

    /**
     * Return the response.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Set the response.
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    #-------------------------------------#
    # Response Headers
    #-------------------------------------#

    /**
     * Add response header from header line string.
     *
     * @throws \InvalidArgumentException  Invalid header line argument
     */
    public function addHeader(string $header_line): self
    {
        $header_parts = explode(':', $header_line, 2);

        if (count($header_parts) !== 2) {
            throw new InvalidArgumentException("'$header_line' is not a valid HTTP header line");
        }

        $header_name  = trim($header_parts[0]);
        $header_value = trim($header_parts[1]);

        if ($this->response->hasHeader($header_name)) {
            $this->response = $this->response->withAddedHeader($header_name, $header_value);
        } else {
            $this->response = $this->response->withHeader($header_name, $header_value);
        }

        return $this;
    }

    /**
     * Set response headers from header line array.
     *
     * @throws \InvalidArgumentException  Invalid status code argument value
     * @throws \UnexpectedValueException  Invalid header value(s)
     */
    public function setHeadersFromArray(array $headers): self
    {
        $status = array_shift($headers);

        $this->setStatus($status);

        foreach ($headers as $header) {
            $header_line = trim($header);

            if ($header_line === '') {
                continue;
            }

            $this->addHeader($header_line);
        }

        return $this;
    }

    /**
     * Set response headers from header line string.
     *
     * @throws \InvalidArgumentException  Header string is not a string on object with __toString()
     * @throws \UnexpectedValueException  Invalid header value(s)
     */
    public function setHeadersFromString(string $headers): self
    {
        if (is_string($headers) || (is_object($headers) && method_exists($headers, '__toString'))) {
            $this->setHeadersFromArray(explode("\r\n", $headers));

            return $this;
        }

        throw new InvalidArgumentException(
            sprintf(
                '%s expects parameter 1 to be a string, %s given',
                __METHOD__,
                is_object($headers) ? get_class($headers) : gettype($headers)
            )
        );
    }

    #-------------------------------------#
    # Response Status
    #-------------------------------------#

    /**
     * Set response status.
     *
     * @throws \InvalidArgumentException  Invalid status line argument
     */
    public function setStatus(string $status_line): self
    {
        $status_parts = explode(' ', $status_line, 3);
        $parts_count  = count($status_parts);

        if ($parts_count < 2 || strpos(strtoupper($status_parts[0]), 'HTTP/') !== 0) {
            throw new InvalidArgumentException("'$status_line' is not a valid HTTP status line");
        }

        $reason_phrase = ($parts_count > 2 ? $status_parts[2] : '');

        $this->response = $this->response
            ->withStatus((int)$status_parts[1], $reason_phrase)
            ->withProtocolVersion(substr($status_parts[0], 5));

        return $this;
    }
}
