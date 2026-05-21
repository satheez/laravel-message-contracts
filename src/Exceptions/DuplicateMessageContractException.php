<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class DuplicateMessageContractException extends MessageContractsException
{
    public static function for(string $contract, int $version, string $existing, string $duplicate): self
    {
        return new self(
            "Duplicate message contract registration detected.\n\n".
            "Contract: {$contract}\n".
            "Version: {$version}\n\n".
            "Registered classes:\n".
            "  - {$existing}\n".
            "  - {$duplicate}\n\n".
            'Each contract/version pair must be unique.'
        );
    }
}
