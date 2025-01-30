<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests;

use Closure;
use LogicException;
use SensitiveParameter;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Represents a configurable HTTP request.
 *
 * @phpstan-type TOptions array<string, mixed>&array{
 *     vars?: array<string, mixed>,
 *     headers?: array<string, string|string[]>,
 * }
 */
final readonly class Request
{
    /**
     * @param TOptions $options
     */
    private function __construct(
        private HttpClientInterface $http,
        private SerializerInterface $serializer,
        private string $requestFormat,
        private string $responseFormat,
        public array $options,
        public ?string $method = null,
        public ?string $path = null,
    ) {
    }

    /**
     * Creates a new Request instance.
     *
     * @param HttpClientInterface $http           HTTP client for making requests
     * @param SerializerInterface $serializer     Serializer for request and response data
     * @param string              $requestFormat  Default format of the request body
     * @param string              $responseFormat Default format of the response body
     * @param TOptions            $options        Additional http client options
     */
    public static function create(
        HttpClientInterface $http,
        SerializerInterface $serializer,
        string $requestFormat = 'json',
        string $responseFormat = 'json',
        array $options = [],
    ): self {
        return new self(
            http: $http,
            serializer: $serializer,
            requestFormat: $requestFormat,
            responseFormat: $responseFormat,
            options: $options,
        );
    }

    /**
     * Sets the base URI for the request.
     *
     * @param string $uri Base URI
     */
    public function base(string $uri): self
    {
        return $this->with(['base_uri' => $uri]);
    }

    /**
     * Sets the HTTP method to GET and the path.
     *
     * @param string $path Request path
     */
    public function get(string $path): self
    {
        return $this->with(method: 'GET', path: $path);
    }

    /**
     * Sets the HTTP method to POST and the path.
     *
     * @param string $path Request path
     */
    public function post(string $path): self
    {
        return $this->with(method: 'POST', path: $path);
    }

    /**
     * Sets the HTTP method to PUT and the path.
     *
     * @param string $path Request path
     */
    public function put(string $path): self
    {
        return $this->with(method: 'PUT', path: $path);
    }

    /**
     * Sets the HTTP method to PATCH and the path.
     *
     * @param string $path Request path
     */
    public function patch(string $path): self
    {
        return $this->with(method: 'PATCH', path: $path);
    }

    /**
     * Sets the HTTP method to DELETE and the path.
     *
     * @param string $path Request path
     */
    public function delete(string $path): self
    {
        return $this->with(method: 'DELETE', path: $path);
    }

    /**
     * Sets the HTTP method to OPTIONS and the path.
     *
     * @param string $path Request path
     */
    public function options(string $path): self
    {
        return $this->with(method: 'OPTIONS', path: $path);
    }

    /**
     * Adds or updates request path variables.
     *
     * @param array<string, mixed> $vars      Variables to add or update
     * @param bool                 $overwrite Whether to overwrite existing variables
     */
    public function vars(array $vars, bool $overwrite = false): self
    {
        if (!$overwrite) {
            $vars = array_merge($this->options['vars'] ?? [], $vars);
        }

        return $this->with(['vars' => $vars]);
    }

    /**
     * Adds or updates a single request path variable.
     *
     * @param string $name  Variable name
     * @param mixed  $value Variable value
     */
    public function var(string $name, mixed $value): self
    {
        return $this->vars([$name => $value]);
    }

    /**
     * Adds or updates request headers.
     *
     * @param array<string, string|string[]> $headers   Headers to add or update
     * @param bool                           $overwrite Whether to overwrite existing headers
     */
    public function headers(array $headers, bool $overwrite = false): self
    {
        if (!$overwrite) {
            $headers = array_merge($this->options['headers'] ?? [], $headers);
        }

        return $this->with(['headers' => $headers]);
    }

    /**
     * Adds or updates a single request header.
     *
     * @param string $key   Header name
     * @param string $value Header value
     */
    public function header(string $key, string $value): self
    {
        return $this->headers([$key => $value]);
    }

    /**
     * Sets the query parameters for the request.
     *
     * @param array<string, string> $query Query parameters
     */
    public function query(array $query): self
    {
        return $this->with(['query' => $query]);
    }

    /**
     * Sets the body content of the request after serializing it.
     *
     * @param mixed                $body    Body content
     * @param string|null          $format  Serialization format
     * @param array<string, mixed> $context Serialization context
     */
    public function body(mixed $body, ?string $format = null, array $context = []): self
    {
        $body = $this->serializer->serialize($body, $format ?? $this->requestFormat, $context);

        return $this->with(['body' => $body]);
    }

    /**
     * Sets the body content to the symfony/http-client as is.
     *
     * @param string|resource|Closure|iterable<array-key, mixed> $body Body content
     */
    public function rawBody(mixed $body): self
    {
        return $this->with(['body' => $body]);
    }

    /**
     * Sets basic authentication credentials.
     *
     * @param string $user     Username
     * @param string $password Password
     */
    public function basic(string $user, #[SensitiveParameter] string $password = ''): self
    {
        $auth = [$user];
        if ('' !== $password) {
            $auth[] = $password;
        }

        return $this->with(['auth_basic' => $auth]);
    }

    /**
     * Sets bearer token authentication.
     *
     * @param string $token Bearer token
     */
    public function bearer(#[SensitiveParameter] string $token): self
    {
        return $this->with(['auth_bearer' => $token]);
    }

    /**
     * Executes the request and returns a Response object.
     */
    public function response(): Response
    {
        if (null === $this->method || null === $this->path) {
            throw new LogicException('The HTTP method and path were not set');
        }

        return new Response(
            http: $this->http,
            serializer: $this->serializer,
            format: $this->responseFormat,
            request: $this,
            inner: $this->http->request($this->method, $this->path, $this->options),
        );
    }

    /**
     * @param TOptions $options
     */
    private function with(
        ?array $options = null,
        ?string $method = null,
        ?string $path = null,
    ): self {
        return new self(
            http: $this->http,
            serializer: $this->serializer,
            requestFormat: $this->requestFormat,
            responseFormat: $this->responseFormat,
            options: $options ? array_merge($this->options, $options) : $this->options,
            method: $method ?? $this->method,
            path: $path ?? $this->path,
        );
    }
}
