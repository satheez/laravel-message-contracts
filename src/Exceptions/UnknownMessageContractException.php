<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class UnknownMessageContractException extends MessageContractsException
{
    public static function for(string $contract): self
    {
        return new self(
            "Unknown message contract.\n\n".
            "Contract: {$contract}\n\n".
            "No registered message contract was found for this contract name.\n".
            'Run `php artisan message-contracts:list` to see all registered contracts.'
        );
    }
}
