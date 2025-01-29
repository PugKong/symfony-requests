<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests;

use RuntimeException;

use function sprintf;

final class StatusCodeException extends RuntimeException
{
    /**
     * @param int[] $expectedStatuses
     */
    public function __construct(
        public readonly array $expectedStatuses,
        public readonly Response $response,
    ) {
        $inner = $this->response->inner;

        parent::__construct(sprintf(
            '%d returned for %s %s, expected %s',
            $inner->getStatusCode(),
            $inner->getInfo('http_method'), // @phpstan-ignore argument.type
            $inner->getInfo('url'), // @phpstan-ignore argument.type
            implode(', ', $expectedStatuses),
        ));
    }
}
