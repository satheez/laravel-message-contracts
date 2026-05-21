<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\Command;
use Satheez\MessageContracts\Contracts\MessageContract;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'message-contracts:list')]
class ListMessageContractsCommand extends Command
{
    protected $name = 'message-contracts:list';

    protected $description = 'List all registered message contracts';

    public function handle(MessageContractRegistry $registry): int
    {
        $contracts = $registry->all();

        if ($contracts === []) {
            $this->components->warn('No message contracts are registered.');
            $this->line('  Add contract classes to the <info>contracts</info> array in <info>config/message-contracts.php</info>.');

            return self::SUCCESS;
        }

        $format = $this->option('format');

        if ($format === 'json') {
            $this->outputJson($contracts);
        } else {
            $this->outputTable($contracts);
        }

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────
    // Output formats
    // ──────────────────────────────────────────────

    /** @param array<class-string<MessageContract>> $contracts */
    private function outputTable(array $contracts): void
    {
        $rows = array_map(fn (string $class): array => [
            $class::contract(),
            $class::version(),
            $class,
            $class::deprecated() ? '<fg=yellow>Yes</>' : 'No',
            $class::example() !== [] ? '<fg=green>Yes</>' : '<fg=red>No</>',
            count($class::rules()),
        ], $contracts);

        $this->table(
            ['Contract', 'Version', 'Class', 'Deprecated', 'Has Example', 'Rules'],
            $rows,
        );

        $this->newLine();
        $this->line('  <info>'.count($contracts).'</info> contract(s) registered.');
    }

    /** @param array<class-string<MessageContract>> $contracts */
    private function outputJson(array $contracts): void
    {
        $data = array_map(fn (string $class): array => [
            'contract' => $class::contract(),
            'version' => $class::version(),
            'class' => $class,
            'deprecated' => $class::deprecated(),
            'has_example' => $class::example() !== [],
            'rules_count' => count($class::rules()),
        ], $contracts);

        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    protected function getOptions(): array
    {
        /** @phpstan-ignore return.type */
        return [
            ['format', null, InputOption::VALUE_OPTIONAL, 'Output format: table or json', 'table'],
        ];
    }
}
