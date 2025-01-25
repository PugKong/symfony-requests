<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

use function in_array;

/**
 * Represents an HTTP response with enhanced functionality for status checking and content deserialization.
 */
final readonly class Response
{
    /**
     * @param HttpClientInterface $http       The HTTP client instance
     * @param SerializerInterface $serializer The serializer for content deserialization
     * @param string              $format     The default format for deserialization
     * @param Request             $request    The associated HTTP request
     * @param ResponseInterface   $inner      The wrapped HTTP response
     */
    public function __construct(
        private HttpClientInterface $http,
        private SerializerInterface $serializer,
        private string $format,
        public Request $request,
        public ResponseInterface $inner,
    ) {
    }

    /**
     * Validates the response status code against the expected codes.
     *
     * @param int ...$expected The expected status codes
     *
     * @throws StatusCodeException If the status code does not match any of the expected values
     */
    public function checkStatus(int ...$expected): self
    {
        if (!in_array($this->status(), $expected, true)) {
            throw new StatusCodeException($expected, $this);
        }

        return $this;
    }

    /**
     * Retrieves the HTTP status code of the response.
     *
     * @throws TransportExceptionInterface If a network error occurs
     */
    public function status(): int
    {
        return $this->inner->getStatusCode();
    }

    /**
     * Retrieves all response headers.
     *
     * @return array<string, string[]> An associative array of header names and their values
     *
     * @throws TransportExceptionInterface If a network error occurs
     */
    public function headers(): array
    {
        return $this->inner->getHeaders(false);
    }

    /**
     * Retrieves a specific header by name.
     *
     * @param string $name The header name (case-insensitive)
     *
     * @return string[] An array of header values
     *
     * @throws TransportExceptionInterface If a network error occurs
     */
    public function header(string $name): array
    {
        return $this->headers()[strtolower($name)] ?? [];
    }

    /**
     * Deserializes the response content into an object of the specified class.
     *
     * @template T
     *
     * @param class-string<T>      $class   The target class for deserialization
     * @param string|null          $format  The format for deserialization
     * @param array<string, mixed> $context Additional context for the deserializer
     *
     * @return T The deserialized object
     *
     * @throws TransportExceptionInterface If a network error occurs
     */
    public function object(string $class, ?string $format = null, array $context = []): mixed
    {
        return $this->serializer->deserialize(
            data: $this->content(),
            type: $class,
            format: $format ?? $this->format,
            context: $context,
        );
    }

    /**
     * Deserializes the response content into an array of objects of the specified class.
     *
     * @template T
     *
     * @param class-string<T>      $class   The target class for deserialization
     * @param string|null          $format  The format for deserialization
     * @param array<string, mixed> $context Additional context for the deserializer
     *
     * @return T[] The array of deserialized objects
     *
     * @throws TransportExceptionInterface If a network error occurs
     */
    public function objects(string $class, ?string $format = null, array $context = []): array
    {
        // @phpstan-ignore-next-line return.type
        return $this->serializer->deserialize(
            data: $this->content(),
            type: $class.'[]',
            format: $format ?? $this->format,
            context: $context,
        );
    }

    /**
     * Retrieves the raw response content as a string.
     *
     * @return string The raw response content
     *
     * @throws TransportExceptionInterface If a network error occurs
     */
    public function content(): string
    {
        return $this->inner->getContent(false);
    }

    /**
     * Converts the response content into an associative array.
     *
     * @return array<array-key, mixed> The response content as an associative array
     *
     * @throws TransportExceptionInterface If a network error occurs
     */
    public function array(): array
    {
        return $this->inner->toArray(false);
    }

    /**
     * Yields response chunk by chunk.
     *
     * @param float|null $timeout The idle timeout before yielding timeout chunk
     */
    public function stream(?float $timeout = null): ResponseStreamInterface
    {
        return $this->http->stream($this->inner, $timeout);
    }
}
