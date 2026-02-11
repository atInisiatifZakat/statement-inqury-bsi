<?php

declare(strict_types=1);

namespace Inisiatif\Bsi;

final class Money
{
    public function __construct(
        public readonly float|int|string $value,
        public readonly string $currency = 'IDR',
    ) {}
}
