## HTTP Client Library

Lightweight [PSR-7 HTTP message interfaces](https://www.php-fig.org/psr/psr-7/) cURL client with support for [PSR-17 HTTP factories interfaces](https://www.php-fig.org/psr/psr-17/).

## Installation

Install via [Composer](https://getcomposer.org/).

```shell
composer require pdeans/http
```

## Usage

The cURL client is built on top of the [Laminas Diactoros](https://docs.laminas.dev/laminas-diactoros/) PSR-7 and PSR-17 implementations.

### Configuring the Client

The client accepts an optional associative array of [curl options](http://php.net/curl_setopt) as the first parameter to configure the cURL client. Please note that the following cURL options cannot be set in order to comply with PSR-7 standards. Instead, these options should be provided as part of the request options:

- CURLOPT_CUSTOMREQUEST
- CURLOPT_FOLLOWLOCATION
- CURLOPT_HEADER
- CURLOPT_HTTP_VERSION
- CURLOPT_HTTPHEADER
- CURLOPT_NOBODY
- CURLOPT_POSTFIELDS
- CURLOPT_RETURNTRANSFER
- CURLOPT_URL
- CURLOPT_USERPWD

The following is an example of how to create and configure the client:

```php
use pdeans\Http\Client;

$client = new Client();

// With options
$client = new Client([
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => true,
]);
```

### HTTP Requests

The client comes bundled with helper methods to provide a convenient way for issuing the supported HTTP request methods.

`GET`, `HEAD`, and `TRACE` methods take the following parameters:

- String representation of the target url **OR** a class instance that implements the PSR-7 `Psr\Http\Message\UriInterface`.
- Associative array of headers `[headerName => headerValue]`.

`POST`, `PUT`, `PATCH`, `OPTIONS`, and `DELETE` methods take the two parameters listed above, plus an optional 3rd parameter for the request body:

- String of request body data **OR** a class instance that implements the PSR-7 `Psr\Http\Message\StreamInterface` **OR** a `resource`.

#### Request Usage

```php
// GET request with header
$response = $client->get('https://example.com/1', ['custom-header' => 'header/value']);

// GET request without header
$response = $client->get('https://example.com/2');

// HEAD request
$response = $client->head('https://example.com/2');

// TRACE request
$response = $client->trace('https://example.com/2');

$headers = [
    'Content-Type'  => 'application/json',
    'Accept'        => 'application/json',
    'Authorization' => 'Basic ' . base64_encode('username:password'),
];

$data = json_encode(['json' => 'json data']);

// POST request with headers and request body
$response = $client->post('https://example.com/4', $headers, $data);

// PUT request
$response = $client->put('https://example.com/4', $headers, $data);

// PATCH request
$response = $client->patch('https://example.com/4', $headers, $data);

// OPTIONS request
$response = $client->options('https://example.com/4', $headers, $data);

// DELETE request
$response = $client->delete('https://example.com/4', $headers, $data);
```

If more control over the request is needed, the helper methods can be bypassed and the `sendRequest` method may be called directly. This method accepts a class instance that implements the PSR-7 `Psr\Http\Message\RequestInterface`.

Example `GET` request using the `RequestFactory` class instance:

```php
use pdeans\Http\Factories\RequestFactory;

$request = (new RequestFactory())->createRequest('GET', 'https://example.com/1');

$response = $client->sendRequest($request);
```

Example `POST` request using the `Request` class instance:

```php
use pdeans\Http\Request;

$request = new Request(
    uri: 'https://example.com',
    method: 'POST',
    headers: ['Content-Type' => 'application/json'],
    body: $client->getStream(json_encode(['json' => 'json data']))
);

$response = $client->sendRequest($request);
```

### HTTP Responses

Each HTTP request returns a `pdeans\Http\Response` class instance, which is an implementation of the PSR-7 `Psr\Http\Message\ResponseInterface`.

#### Response Usage

```php
// Issue request
$response = $client->get('https://example.com/1', ['custom-header' => 'header/value']);

// Response body output
echo (string) $response->getBody();

// Response headers output
var_dump($response->getHeaders());
var_dump($response->getHeader('custom-header'));
var_dump($response->hasHeader('custom-header'));
echo $response->getHeaderLine('custom-header');

// Response status code output
echo $response->getStatusCode();

// Response reason phrase output
echo $response->getReasonPhrase();
```

### PSR-17 Factories

The following HTTP factory classes are available and each implement their associated PSR-17 factory interface:

- `pdeans\Http\Factories\RequestFactory` implements `Psr\Http\Message\RequestFactoryInterface`
- `pdeans\Http\Factories\ResponseFactory` implements `Psr\Http\Message\ResponseFactoryInterface`
- `pdeans\Http\Factories\ServerRequestFactory` implements `Psr\Http\Message\ServerRequestFactoryInterface`
- `pdeans\Http\Factories\StreamFactory` implements `Psr\Http\Message\StreamFactoryInterface`
- `pdeans\Http\Factories\UploadedFileFactory` implements `Psr\Http\Message\UploadedFileFactoryInterface`
- `pdeans\Http\Factories\UriFactory` implements `Psr\Http\Message\UriFactoryInterface`

#### Factory Usage

```php
use pdeans\Http\Factories\RequestFactory;
use pdeans\Http\Factories\ResponseFactory;
use pdeans\Http\Factories\ServerRequestFactory;
use pdeans\Http\Factories\StreamFactory;
use pdeans\Http\Factories\UploadedFileFactory;
use pdeans\Http\Factories\UriFactory;

// Psr\Http\Message\RequestFactoryInterface
$requestFactory = new RequestFactory();

// Psr\Http\Message\RequestInterface
$request = $requestFactory->createRequest('GET', 'https://example.com/1');

// Psr\Http\Message\ResponseFactoryInterface
$responseFactory = new ResponseFactory();

// Psr\Http\Message\ResponseInterface
$response = $responseFactory->createResponse();

// Psr\Http\Message\ServerRequestFactoryInterface
$serverRequestFactory = new ServerRequestFactory();

// Psr\Http\Message\ServerRequestInterface
$serverRequest = $serverRequestFactory->createServerRequest('GET', 'https://example.com/2');

// Psr\Http\Message\StreamFactoryInterface
$streamFactory = new StreamFactory();

// Psr\Http\Message\StreamInterface
$stream = $streamFactory->createStream();
$fileStream = $streamFactory->createStreamFromFile('dir/api.json');
$resourceStream = $streamFactory->createStreamFromResource(fopen('php://temp', 'r+'));

// Psr\Http\Message\UploadedFileFactoryInterface
$uploadedFileFactory = new UploadedFileFactory();

// Psr\Http\Message\UploadedFileInterface
$uploadedFile = $uploadedFileFactory->createUploadedFile($fileStream);

// Psr\Http\Message\UriFactoryInterface
$uriFactory = new UriFactory();

// Psr\Http\Message\UriInterface
$uri = $uriFactory->createUri();
```

## Further Reading

As this library is a layer built upon existing libraries and standards, it is encouraged that you read through the documentation of these libraries and standards to get a better understanding of how the various components work.

- [PSR-7: HTTP Message Interfaces](https://www.php-fig.org/psr/psr-7/)
- [PSR-17: HTTP Factories Interfaces](https://www.php-fig.org/psr/psr-17/)
- [Laminas Diactoros Library](https://docs.laminas.dev/laminas-diactoros/)
- [PHP Client URL Library](https://www.php.net/manual/en/book.curl.php)
