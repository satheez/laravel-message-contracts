<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class MessageSerializationException extends MessageContractsException
{
    public static function encodingFailed(string $contract, int $version, string $reason): self
    {
        return new self(
            "Message serialization failed.\n\n".
            "Contract: {$contract}\n".
            "Version: {$version}\n\n".
            "Reason: {$reason}"
        );
    }
}
