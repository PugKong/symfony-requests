<?php

declare(strict_types=1);

namespace Pugkong\Symfony\Requests\Tests;

final readonly class NameRequest
{
    public function __construct(public string $name)
    {
    }
}
