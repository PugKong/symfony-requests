<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests\Tests;

final readonly class EchoServerResponse
{
    /**
     * @param array<string, string>|null $headers
     * @param array<string, string>|null $query
     * @param array<string, mixed>|null  $body
     */
    public function __construct(
        public string $method,
        public string $path,
        public ?array $headers = null,
        public ?array $query = null,
        public ?array $body = null,
    ) {
    }

    /**
     * @param array<string, string>      $headers
     * @param array<string, string>|null $query
     * @param array<string, mixed>|null  $body
     */
    public static function json(
        string $method,
        string $path,
        array $headers = [],
        ?array $query = null,
        ?array $body = null,
    ): self {
        return new self(
            method: $method,
            path: $path,
            headers: array_merge(
                [
                    'Accept' => 'application/json',
                    'Accept-Encoding' => 'gzip',
                    'User-Agent' => 'symfony-requests',
                ],
                $headers,
            ),
            query: $query,
            body: $body,
        );
    }

    /**
     * @param array<string, string>      $headers
     * @param array<string, string>|null $query
     * @param array<string, mixed>|null  $body
     */
    public static function xml(
        string $method,
        string $path,
        array $headers = [],
        ?array $query = null,
        ?array $body = null,
    ): self {
        return new self(
            method: $method,
            path: $path,
            headers: array_merge(
                [
                    'Accept' => 'application/xml',
                    'Accept-Encoding' => 'gzip',
                    'User-Agent' => 'symfony-requests',
                ],
                $headers,
            ),
            query: $query,
            body: $body,
        );
    }
}
