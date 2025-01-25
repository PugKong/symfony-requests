<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

final readonly class FormEncoder implements EncoderInterface
{
    /**
     * @param array<array-key, mixed> $data
     * @param array<string, mixed>    $context
     */
    public function encode(mixed $data, string $format, array $context = []): string
    {
        return http_build_query($data);
    }

    public function supportsEncoding(string $format): bool
    {
        return 'form' === $format;
    }
}
