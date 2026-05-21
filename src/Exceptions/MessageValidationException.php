<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Exceptions;

class MessageValidationException extends MessageContractsException
{
    /**
     * @param  array<string, string[]>  $errors
     */
    public function __construct(
        private readonly string $contract,
        private readonly int $version,
        private readonly string $contractClass,
        private readonly array $errors,
    ) {
        $errorLines = [];
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $errorLines[] = "  - {$field}: {$message}";
            }
        }

        parent::__construct(
            "Message validation failed.\n\n".
            "Contract: {$contract}\n".
            "Version: {$version}\n".
            "Class: {$contractClass}\n\n".
            "Errors:\n".implode("\n", $errorLines)
        );
    }

    /**
     * @param  array<string, string[]>  $errors
     */
    public static function forContract(
        string $contract,
        int $version,
        string $contractClass,
        array $errors,
    ): self {
        return new self($contract, $version, $contractClass, $errors);
    }

    public function getContract(): string
    {
        return $this->contract;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getContractClass(): string
    {
        return $this->contractClass;
    }

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
