<?php

namespace pdeans\Http;

use CurlHandle;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use pdeans\Http\Builders\ResponseBuilder;
use pdeans\Http\Contracts\ClientInterface;
use pdeans\Http\Exceptions\NetworkException;
use pdeans\Http\Exceptions\RequestException;
use pdeans\Http\Factories\StreamFactory;

class Client implements ClientInterface
{
    /**
     * PSR-7 stream object
     *
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    protected StreamFactoryInterface $streamFactory;

    /**
     * cURL handler
     *
     * @var \CurlHandle|null
     */
    protected CurlHandle|null $ch;

    /**
     * cURL options array
     *
     * @var array
     */
    protected array $options;

    /**
     * Maximum request body size
     *
     * @var int
     */
    protected static int $MAX_BODY_SIZE;

    /**
     * Create a new http client.
     */
    public function __construct(array $options = [])
    {
        $this->streamFactory  = new StreamFactory();
        $this->options = $options;
        $this->ch = null;

        self::$MAX_BODY_SIZE = 1024 * 1024;
    }

    /**
     * Close cURL handler
     */
    public function __destruct()
    {
        $this->release();
    }

    #---------------------------------------------------#
    # Convenience Request Methods
    #---------------------------------------------------#

    /**
     * Send a DELETE request
     */
    public function delete(
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null
    ): ResponseInterface {
        return $this->sendRequest(new Request($uri, 'DELETE', $this->getStream($body), $headers));
    }

    /**
     * Send a GET request
     */
    public function get(UriInterface|string $uri, array $headers = []): ResponseInterface
    {
        return $this->sendRequest(new Request($uri, 'GET', $this->streamFactory->createStream(), $headers));
    }

    /**
     * Send a HEAD request
     */
    public function head(UriInterface|string $uri, array $headers = []): ResponseInterface
    {
        return $this->sendRequest(new Request($uri, 'HEAD', $this->streamFactory->createStream(), $headers));
    }

    /**
     * Send an OPTIONS request
     */
    public function options(
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null
    ): ResponseInterface {
        return $this->sendRequest(new Request($uri, 'OPTIONS', $this->getStream($body), $headers));
    }

    /**
     * Send a PATCH request
     */
    public function patch(
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null
    ): ResponseInterface {
        return $this->sendRequest(new Request($uri, 'PATCH', $this->getStream($body), $headers));
    }

    /**
     * Send a POST request
     */
    public function post(
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null
    ): ResponseInterface {
        return $this->sendRequest(new Request($uri, 'POST', $this->getStream($body), $headers));
    }

    /**
     * Send a PUT request
     */
    public function put(
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null
    ): ResponseInterface {
        return $this->sendRequest(new Request($uri, 'PUT', $this->getStream($body), $headers));
    }

    /**
     * Send a TRACE request
     */
    public function trace(UriInterface|string $uri, array $headers = []): ResponseInterface
    {
        return $this->sendRequest(new Request($uri, 'TRACE', $this->streamFactory->createStream(), $headers));
    }

    #---------------------------------------------------#
    # Send Http Request
    #---------------------------------------------------#

    /**
     * Send a PSR-7 Request
     *
     * @throws \pdeans\Http\Exceptions\NetworkException  Invalid request due to network issue
     * @throws \pdeans\Http\Exceptions\RequestException  Invalid request
     * @throws \InvalidArgumentException  Invalid header names and/or values
     * @throws \RuntimeException  Failure to create stream
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->createResponse();
        $options  = $this->createOptions($request, $response);

        // Reset cURL handler
        if ($this->ch instanceof CurlHandle) {
            if (function_exists('curl_reset')) {
                curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, null);
                curl_setopt($this->ch, CURLOPT_READFUNCTION, null);
                curl_setopt($this->ch, CURLOPT_WRITEFUNCTION, null);
                curl_setopt($this->ch, CURLOPT_PROGRESSFUNCTION, null);

                curl_reset($this->ch);
            } else {
                curl_close($this->ch);

                $this->ch = curl_init();
            }
        } else {
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
     * @throws \RuntimeException  Failure to create stream
     */
    protected function createResponse(): ResponseBuilder
    {
        $body = $this->streamFactory->createStream();

        return new ResponseBuilder(new Response($body, 200, []));
    }

    #---------------------------------------------------#
    # Request Helpers
    #---------------------------------------------------#

    /**
     * Create array of headers to pass to CURLOPT_HTTPHEADER
     */
    protected function createHeaders(RequestInterface $request, array $options): array
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
                } elseif (!array_key_exists(CURLOPT_READFUNCTION, $options)) {
                    // Force content length to '0' if body is empty
                    $values = [0];
                }
            }

            foreach ($values as $value) {
                $headers[] = $name . ': ' . $value;
            }
        }

        // Although cURL does not support 'Expect-Continue', it adds the 'Expect'
        // header by default, so we need to force 'Expect' to empty.
        $headers[] = 'Expect:';

        return $headers;
    }

    /**
     * Return a StreamInterface instance for the provided stream value.
     */
    public function getStream(mixed $stream = null): StreamInterface
    {
        if ($stream instanceof StreamInterface) {
            return $stream;
        }

        return is_resource($stream)
            ? $this->streamFactory->createStreamFromResource($stream)
            : $this->streamFactory->createStream($stream);
    }

    /**
     * Release the request handler.
     */
    public function release(): void
    {
        if ($this->ch instanceof CurlHandle) {
            curl_close($this->ch);

            $this->ch = null;
        }
    }

    #---------------------------------------------------#
    # cURL Options
    #---------------------------------------------------#

    /**
     * Create cURL request options
     *
     * @throws \pdeans\Http\Exceptions\RequestException  Invalid request
     * @throws \InvalidArgumentException  Invalid header names and/or values
     * @throws \RuntimeException  Unable to read request body
     */
    protected function createOptions(RequestInterface $request, ResponseBuilder $response): array
    {
        $options = $this->options;

        // These options default to false and cannot be changed on set up.
        // The options should be provided with the request instead.
        $options[CURLOPT_FOLLOWLOCATION] = false;
        $options[CURLOPT_HEADER]         = false;
        $options[CURLOPT_RETURNTRANSFER] = false;

        try {
            $options[CURLOPT_HTTP_VERSION] = $this->getProtocolVersion($request->getProtocolVersion());
        } catch (UnexpectedValueException $e) {
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
                } else {
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
     */
    protected function addRequestBodyOptions(RequestInterface $request, array $options): array
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
                } else {
                    $options[CURLOPT_POSTFIELDS] = (string)$body;
                }
            }
        }

        if ($request->getMethod() === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
        } elseif ($request->getMethod() !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
        }

        return $options;
    }

    /**
     * Get cURL constant for request http protocol version
     *
     * @throws \UnexpectedValueException  Unsupported cURL http protocol version
     */
    protected function getProtocolVersion(string $request_protocol_version): int
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
