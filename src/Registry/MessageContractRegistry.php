<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Registry;

use Satheez\MessageContracts\Contracts\MessageContract;
use Satheez\MessageContracts\Exceptions\DuplicateMessageContractException;
use Satheez\MessageContracts\Exceptions\InvalidMessageContractException;
use Satheez\MessageContracts\Exceptions\UnknownMessageContractException;
use Satheez\MessageContracts\Exceptions\UnknownMessageContractVersionException;

/**
 * Stores and resolves registered message contract classes.
 *
 * Internal map structure:
 *   [contract_name => [version => ContractClass::class]]
 */
final class MessageContractRegistry
{
    /**
     * @var array<string, array<int, class-string<MessageContract>>>
     */
    private array $contracts = [];

    /**
     * Register a message contract class.
     *
     * @param  class-string<MessageContract>  $contractClass
     *
     * @throws InvalidMessageContractException
     * @throws DuplicateMessageContractException
     */
    public function register(string $contractClass): void
    {
        $this->assertValidContract($contractClass);

        $name = $contractClass::contract();
        $version = $contractClass::version();

        if (isset($this->contracts[$name][$version])) {
            throw DuplicateMessageContractException::for(
                $name,
                $version,
                $this->contracts[$name][$version],
                $contractClass,
            );
        }

        $this->contracts[$name][$version] = $contractClass;
    }

    /**
     * Resolve a contract class by name and version.
     *
     * @return class-string<MessageContract>
     *
     * @throws UnknownMessageContractException
     * @throws UnknownMessageContractVersionException
     */
    public function resolve(string $contract, int $version): string
    {
        if (! isset($this->contracts[$contract])) {
            throw UnknownMessageContractException::for($contract);
        }

        if (! isset($this->contracts[$contract][$version])) {
            throw UnknownMessageContractVersionException::for(
                $contract,
                $version,
                array_keys($this->contracts[$contract]),
            );
        }

        return $this->contracts[$contract][$version];
    }

    /**
     * Check whether a contract/version pair is registered.
     */
    public function has(string $contract, int $version): bool
    {
        return isset($this->contracts[$contract][$version]);
    }

    /**
     * Return all registered contract classes as a flat array.
     *
     * @return array<class-string<MessageContract>>
     */
    public function all(): array
    {
        $flat = [];
        foreach ($this->contracts as $versions) {
            foreach ($versions as $class) {
                $flat[] = $class;
            }
        }

        return $flat;
    }

    /**
     * Return all known versions for a given contract name.
     *
     * @return int[]
     */
    public function versionsFor(string $contract): array
    {
        return array_keys($this->contracts[$contract] ?? []);
    }

    // ──────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────

    /**
     * @param  class-string  $contractClass
     *
     * @throws InvalidMessageContractException
     */
    private function assertValidContract(string $contractClass): void
    {
        if (! is_subclass_of($contractClass, MessageContract::class)) {
            throw InvalidMessageContractException::notAMessageContract($contractClass);
        }

        if (trim($contractClass::contract()) === '') {
            throw InvalidMessageContractException::emptyContractName($contractClass);
        }

        if ($contractClass::version() < 1) {
            throw InvalidMessageContractException::invalidVersion($contractClass);
        }
    }
}
