<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class InvalidMessageContractException extends MessageContractsException
{
    public static function notAMessageContract(string $class): self
    {
        return new self(
            "Invalid message contract definition. [{$class}] does not extend Satheez\\MessageContracts\\Contracts\\MessageContract."
        );
    }

    public static function emptyContractName(string $class): self
    {
        return new self(
            "Invalid message contract definition. [{$class}]::contract() must return a non-empty string."
        );
    }

    public static function invalidVersion(string $class): self
    {
        return new self(
            "Invalid message contract definition. [{$class}]::version() must return an integer >= 1."
        );
    }
}
