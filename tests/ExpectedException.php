<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests\Tests;

use Throwable;

final readonly class ExpectedException
{
    /**
     * @param class-string<Throwable> $class
     */
    public function __construct(
        public string $class,
        public string $message,
    ) {
    }
}
