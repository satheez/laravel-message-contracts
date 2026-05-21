<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class InvalidMessageException extends MessageContractsException
{
    /** @param string[] $missingKeys */
    public static function missingKeys(array $missingKeys): self
    {
        $keyList = implode("\n", array_map(fn (string $k): string => "  - {$k}", $missingKeys));

        return new self(
            "Invalid message structure. Missing required top-level keys:\n{$keyList}\n\n".
            "Expected structure:\n".
            "  {\n".
            "    \"contract\": \"<name>\",\n".
            "    \"version\": <integer>,\n".
            "    \"payload\": { ... }\n".
            '  }'
        );
    }

    public static function notAnArray(): self
    {
        return new self('Invalid message: the message must be an associative array/object, not a list or scalar.');
    }
}
