# Symfony Requests

[![License](https://img.shields.io/github/license/pugkong/symfony-requests)](LICENSE)
[![Workflow: CI](https://github.com/PugKong/symfony-requests/actions/workflows/ci.yml/badge.svg)](https://github.com/PugKong/symfony-requests/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/PugKong/symfony-requests/badge.svg?branch=main)](https://coveralls.io/github/PugKong/symfony-requests?branch=main)
[![Packagist Version](https://img.shields.io/packagist/v/pugkong/symfony-requests)](https://packagist.org/packages/pugkong/symfony-requests)

**Symfony Requests** is a lightweight and flexible library that simplifies making HTTP requests and handling responses
in Symfony-based PHP applications. It leverages Symfony's `HttpClient` and `Serializer` components while providing
additional functionality, such as URI templating and streamlined request-response workflows.

## 🚀 Installation

Install the library via [Composer](https://getcomposer.org/)

```bash
composer require pugkong/symfony-requests
```

## 🌟 Features

- Build and send HTTP requests effortlessly
- Supports URI templates for dynamic endpoint resolution
- Leverages Symfony Serializer for easy data normalization and encoding
- Provides intuitive methods for accessing and deserializing response data

## 🔧 Requirements

- PHP 8.2 or higher
- Symfony HttpClient and Serializer components
- Optionally, a URI template implementation (e.g., `guzzlehttp/uri-template`) for URI template support

## 📄 Usage Example

Below is a complete example showcasing how to use the library for building and sending requests, deserializing
responses, and handling errors

```php
<?php

declare(strict_types=1);

use Pugkong\Symfony\Requests;
use Symfony\Component\HttpClient;
use Symfony\Component\Serializer;

// Create a basic HTTP client instance
$http = HttpClient\HttpClient::create();

// If URI templates are needed, wrap the client with UriTemplateHttpClient
// Requires an implementation such as guzzlehttp/uri-template
$http = new HttpClient\UriTemplateHttpClient($http);

// Initialize a serializer for request/response data handling
$serializer = new Serializer\Serializer(
    normalizers: [
        // Handles arrays to objects conversion
        new Serializer\Normalizer\ArrayDenormalizer(),
        // Handles object normalization
        new Serializer\Normalizer\ObjectNormalizer(),
    ],
    encoders: [
        new Serializer\Encoder\JsonEncoder(), // For handling JSON data
        new Serializer\Encoder\XmlEncoder(), // For handling XML data
        // For sending requests in application/x-www-form-urlencoded format
        new Requests\FormEncoder(),
    ],
);

// Create a base request instance, setting the base URL and default headers
$request = Requests\Request::create($http, $serializer)
    ->base('http://localhost:8000') // Base URL for all requests
    ->header('Accept', 'application/json') // Default header to accept JSON responses
;

final readonly class RequestData
{
    public function __construct(public string $data)
    {
    }
}

final readonly class ResponseData
{
    public function __construct(public string $data)
    {
    }
}

final readonly class ErrorData
{
    public function __construct(public string $error)
    {
    }
}

try {
    // Build and send a PUT request to update a resource with ID 42
    $response = $request
        ->header('Content-Type', 'application/json')
        ->put('/resource/{id}')
        ->var('id', 42)
        ->body(new RequestData('the answer'))
        ->response() // Execute the request and retrieve the response
        ->checkStatus(200) // Ensure the response has a status code of 200
    ;

    // Access response details
    $response->headers(); // Returns response headers as an array
    $response->status(); // Returns HTTP status code as an integer
    $response->content(); // Returns raw response body as a string
     // Deserialize response body into ResponseData object
    $response->object(ResponseData::class);
     // Deserialize response body into an array of ResponseData objects
    $response->objects(ResponseData::class);
    $response->array(); // Parse response content into an associative PHP array
} catch (HttpClient\Exception\TransportException) {
    // Handle network-related exceptions
} catch (Requests\StatusCodeException $e) {
    // Handle unexpected HTTP status codes

    // Access the response instance from the exception to analyze further
    $e->response->status(); // Get the unexpected status code
    // Deserialize error response content into ErrorData
    $e->response->object(ErrorData::class);
}
```

## 📜 License

This project is licensed under [The Unlicense](https://unlicense.org/), which allows you to use, modify, and distribute
the library without restrictions.

## 🛠️ Contributing

Contributions, issues, and feature requests are welcome! Feel free to check out
the [issues page](https://github.com/pugkong/symfony-requests/issues) to report bugs or suggest improvements.

## 🤝 Support

If you find this library useful, consider giving it a ⭐ on [GitHub](https://github.com/pugkong/symfony-requests)!
