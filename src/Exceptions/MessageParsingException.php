<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class MessageParsingException extends MessageContractsException
{
    public static function invalidJson(string $reason): self
    {
        return new self("Failed to parse message: the body is not valid JSON. {$reason}");
    }

    public static function notAnObject(): self
    {
        return new self('Failed to parse message: the JSON body must decode to an object, not a list or scalar.');
    }
}
