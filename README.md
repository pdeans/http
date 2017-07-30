## PHP PSR-7 cURL HTTP Client

Lightweight cURL client intergration of the PHP [PSR-7](http://www.php-fig.org/psr/psr-7/) HTTP message interface.

### Installation

Install via [Composer](https://getcomposer.org/).

```
$ composer require pdeans/http
```

### Usage

The cURL client is built on top of Zend Framework's [Diactoros](https://zendframework.github.io/zend-diactoros/) strict PSR-7 implementation.

#### Configuring the Client

The client accepts an optional associative array of [curl options](http://php.net/curl_setopt) as the first parameter to configure the cURL client. However, please note that the following cURL options cannot be set in order to comply with PSR-7 standards. Instead, these options should be provided as part of the request options:

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

The following is an example of how to create and configure the client with options.

```php
use pdeans\Http\Client;

$client = new Client([
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_SSL_VERIFYHOST => 0,
]);
```

On a side note, the client may also accept optional 2nd and 3rd parameters of `pdeans\Http\Contracts\MessageFactoryInterface` and `pdeans\Http\Contracts\StreamFactoryInterface` implementations respectively. The client uses instances of `pdeans\Http\Factories\MessageFactory` and `pdeans\Http\Factories\StreamFactory` by default if these parameters are omitted.

#### HTTP Requests

The client comes bundled with helper methods to provide a convenient way for issuing the supported HTTP request methods.

`GET`, `HEAD`, and `TRACE` methods take the following parameters:

- String representation of the target url **OR** an object implmentation of `Psr\Http\Message\UriInterface`
- Associative array of headers `(header_name => header_value)`

`POST`, `PUT`, `PATCH`, `OPTIONS`, and `DELETE` methods take the two parameters listed above, plus the following 3rd parameter for the request body:

- String of request body data **OR** an object implmentation of `Psr\Http\Message\StreamInterface`

Note that the `pdeans\Http\Factories\StreamFactory` and `pdeans\Http\Factories\UriFactory` classes provide create methods to implement the PSR interfaces listed above if the object integrations are needed for the request.

**Example requests:**

```php
// GET request with header
$response = $client->get('http://example.com/1', ['custom-header' => 'header/value']);

// GET request without header
$response = $client->get('http://example.com/2');

$headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
];

$data = json_encode(['json' => 'json data']);

// POST request with headers and request body
$response = $client->post('http://example.com/4', $headers, $data);
```

If more control over the request is needed, the helper methods can be bypassed altogether and the underlying main request method, `sendRequest`, can be called directly. This method accepts an object implementation of `Psr\Http\Message\RequestInterface`. The library provides a factory implementation for creating a request object, `pdeans\Http\Factories\MessageFactory->createRequest()`

#### HTTP Responses

Each HTTP request returns a Response object. The Response object is an instance of the `Zend\Diactoros\Response` class, which implements the `Psr\Http\Message\RequestInterface`.

### Further Reading

As this library is a layer built upon existing libraries and standards, I recommend that you read through some of their documentation to get a better understanding of how the various components work.

- [PSR-7: Http Message Interface](http://www.php-fig.org/psr/psr-7/)
- [Zend Framework: Diactoros Library](https://zendframework.github.io/zend-diactoros/) (PSR-7 Implementation)
- [PHP Client URL Library](http://php.net/manual/en/book.curl.php) (PHP cURL)