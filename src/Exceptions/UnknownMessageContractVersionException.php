<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class UnknownMessageContractVersionException extends MessageContractsException
{
    /** @param int[] $registeredVersions */
    public static function for(string $contract, int $version, array $registeredVersions = []): self
    {
        $versionList = $registeredVersions !== []
            ? implode("\n", array_map(fn (int $v): string => "  - {$v}", $registeredVersions))
            : '  (none registered)';

        return new self(
            "Unknown message contract version.\n\n".
            "Contract: {$contract}\n".
            "Version: {$version}\n\n".
            "Registered versions:\n{$versionList}"
        );
    }
}
