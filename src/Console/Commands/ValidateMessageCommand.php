<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\Command;
use Satheez\MessageContracts\DTO\Message;
use Satheez\MessageContracts\Exceptions\MessageContractsException;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Validation\MessageValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'message-contracts:validate')]
class ValidateMessageCommand extends Command
{
    protected $name = 'message-contracts:validate';

    protected $description = 'Validate a payload or full message against a registered contract';

    public function handle(
        MessageContractRegistry $registry,
        MessageValidator $validator,
    ): int {
        $contractArg = $this->argument('contract');
        $contract = is_string($contractArg) ? $contractArg : '';
        $version = (int) $this->option('contract-version');

        // Resolve file or inline JSON
        $json = $this->resolveInput();

        if ($json === null) {
            $this->components->error('Provide either --file or --json.');

            return self::FAILURE;
        }

        try {
            if ($this->option('message')) {
                // Treat as full message envelope
                $message = Message::fromJson($json);
                $message->validateOrFail();
            } else {
                // Treat as raw payload
                $contractClass = $registry->resolve($contract, $version);
                $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                $validator->validateOrFail($contractClass, $payload);
            }
        } catch (MessageContractsException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Payload is valid.');
        $this->components->twoColumnDetail('Contract', $contract);
        $this->components->twoColumnDetail('Version', (string) $version);

        return self::SUCCESS;
    }

    private function resolveInput(): ?string
    {
        $file = $this->option('file');
        if (is_string($file)) {
            if (! file_exists($file)) {
                $this->components->error("File not found: {$file}");

                return null;
            }

            return file_get_contents($file) ?: null;
        }

        $json = $this->option('json');

        return is_string($json) ? $json : null;
    }

    protected function getArguments(): array
    {
        return [
            ['contract', InputArgument::REQUIRED, 'Contract name (e.g. user.registered)'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            new InputOption('contract-version', null, InputOption::VALUE_OPTIONAL, 'Contract version', 1),
            new InputOption('file', null, InputOption::VALUE_OPTIONAL, 'Path to a JSON file'),
            new InputOption('json', null, InputOption::VALUE_OPTIONAL, 'Inline JSON string'),
            new InputOption('message', null, InputOption::VALUE_NONE, 'Treat the input as a full message envelope (not raw payload)'),
        ];
    }
}
