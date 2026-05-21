<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\Command;
use Satheez\MessageContracts\Compatibility\SnapshotManager;

class SnapshotContractsCommand extends Command
{
    protected $signature = 'message-contracts:snapshot {--output= : Path to save snapshot}';

    protected $description = 'Create a snapshot of all registered message contracts.';

    public function handle(SnapshotManager $manager): int
    {
        $contracts = config('message-contracts.contracts', []);

        if (empty($contracts)) {
            $this->warn('No contracts registered.');

            return self::SUCCESS;
        }

        $output = is_string($this->option('output')) ? $this->option('output') : base_path('message-contracts.snapshot.json');

        $this->info('Generating snapshot...');
        $snapshot = $manager->generateSnapshot($contracts);
        $manager->save($output, $snapshot);

        $this->info("Snapshot saved to {$output}");

        return self::SUCCESS;
    }
}
