<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\Command;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Validation\MessageValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'message-contracts:validate-examples')]
class ValidateMessageExamplesCommand extends Command
{
    protected $name = 'message-contracts:validate-examples';

    protected $description = 'Validate the example() payload of every registered message contract';

    public function handle(
        MessageContractRegistry $registry,
        MessageValidator $validator,
    ): int {
        $contracts = $registry->all();

        if ($contracts === []) {
            $this->components->warn('No message contracts are registered.');

            return self::SUCCESS;
        }

        $this->line('  Validating message contract examples...');
        $this->newLine();

        $failed = 0;
        $skipped = 0;
        $failOnMissing = (bool) $this->option('fail-on-missing-example');

        foreach ($contracts as $class) {
            $label = "{$class::contract()}:v{$class::version()}";
            $example = $class::example();

            if ($example === []) {
                if ($failOnMissing) {
                    $this->components->twoColumnDetail($label, '<fg=yellow>SKIP (no example)</>');
                    $failed++;
                } else {
                    $this->components->twoColumnDetail($label, '<fg=yellow>SKIP</>');
                    $skipped++;
                }

                continue;
            }

            $result = $validator->validate($class, $example);

            if ($result->failed()) {
                $this->components->twoColumnDetail($label, '<fg=red>FAIL</>');
                foreach ($result->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->line("    <fg=red>✗</> <comment>{$field}:</comment> {$message}");
                    }
                }
                $failed++;
            } else {
                $this->components->twoColumnDetail($label, '<fg=green>PASS</>');
            }
        }

        $this->newLine();

        if ($failed > 0) {
            $this->components->error("{$failed} contract example(s) failed validation.");

            return self::FAILURE;
        }

        $total = count($contracts) - $skipped;
        $this->components->info("All {$total} contract example(s) passed validation.".($skipped > 0 ? " ({$skipped} skipped)" : ''));

        return self::SUCCESS;
    }

    protected function getOptions(): array
    {
        /** @phpstan-ignore return.type */
        return [
            ['fail-on-missing-example', null, InputOption::VALUE_NONE, 'Treat contracts with no example() as failures'],
        ];
    }
}
