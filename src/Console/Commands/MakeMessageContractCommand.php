<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:message-contract')]
class MakeMessageContractCommand extends GeneratorCommand
{
    protected $name = 'make:message-contract';

    protected $description = 'Create a new message contract class';

    protected $type = 'MessageContract';

    // ──────────────────────────────────────────────
    // Generator
    // ──────────────────────────────────────────────

    protected function getStub(): string
    {
        $publishedStub = base_path('stubs/message-contract.stub');

        return file_exists($publishedStub)
            ? $publishedStub
            : __DIR__.'/../../../resources/stubs/message-contract.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return config('message-contracts.contracts_namespace', $rootNamespace.'\\MessageContracts');
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $version = (int) $this->option('contract-version');
        $contractName = is_string($this->option('contract')) ? $this->option('contract') : $this->inferContractName($name);

        return str_replace(
            ['{{ version }}', '{{ contractName }}'],
            [(string) $version, $contractName],
            $stub,
        );
    }

    /**
     * Append the version suffix to the class name before writing the file.
     * E.g. "UserRegistered" → "UserRegisteredV1Message"
     */
    protected function getNameInput(): string
    {
        $nameArg = $this->argument('name');
        $name = is_string($nameArg) ? trim($nameArg) : '';
        $version = (int) $this->option('contract-version');

        // Strip any existing V\d+Message / V\d+Payload suffix so we don't double-append.
        $base = preg_replace('/V\d+(Message|Payload)$/', '', $name);

        return "{$base}V{$version}Message";
    }

    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption('contract-version', null, InputOption::VALUE_OPTIONAL, 'Contract version', 1),
            new InputOption('contract', null, InputOption::VALUE_OPTIONAL, 'Contract name (e.g. user.registered)'),
        ];
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function inferContractName(string $qualifiedName): string
    {
        // e.g. App\MessageContracts\UserRegisteredV1Message → user.registered
        $short = class_basename($qualifiedName);
        // Remove V\d+Message suffix
        $short = preg_replace('/V\d+Message$/', '', $short) ?? $short;
        // CamelCase → snake_case words joined by dots
        $snake = strtolower((string) preg_replace('/([A-Z])/', '.$1', lcfirst($short)));

        return ltrim($snake, '.');
    }
}
