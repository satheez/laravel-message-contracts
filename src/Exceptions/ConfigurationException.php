<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class ConfigurationException extends MessageContractsException
{
    public static function invalidContractClass(string $class): self
    {
        return new self(
            "Invalid message contracts configuration. The configured contract class does not exist: {$class}"
        );
    }

    public static function missingContractsPath(string $path): self
    {
        return new self(
            "Invalid message contracts configuration. The configured contracts path does not exist: {$path}"
        );
    }
}
