<?php

declare(strict_types=1);

namespace Inisiatif\Bsi;

final readonly class Statement
{
    public function __construct(
        public readonly Money $balance,
        public readonly Money $amount,
        public readonly string $transactionId,
        public readonly string $type,
        public readonly string $remark,
        public readonly ?string $transactionDate = null,
    ) {}
}
