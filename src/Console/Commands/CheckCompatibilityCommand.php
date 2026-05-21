<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\Command;
use Satheez\MessageContracts\Compatibility\SchemaComparator;
use Satheez\MessageContracts\Compatibility\SnapshotManager;

class CheckCompatibilityCommand extends Command
{
    protected $signature = 'message-contracts:check-breaking-changes 
                            {--against= : Path to previous snapshot file}
                            {--fail-on-warning : Treat warnings as CI failures}';

    protected $description = 'Check for breaking changes against a previous snapshot.';

    public function handle(SnapshotManager $manager, SchemaComparator $comparator): int
    {
        $against = is_string($this->option('against')) ? $this->option('against') : base_path('message-contracts.snapshot.json');

        if (! file_exists($against)) {
            $this->error("Snapshot file not found: {$against}");

            return 2;
        }

        $contracts = config('message-contracts.contracts', []);
        $currentSnapshot = $manager->generateSnapshot($contracts);
        $previousSnapshot = $manager->load($against);

        $report = $comparator->compare($previousSnapshot, $currentSnapshot);
        $changes = $report->getChanges();

        if ($changes === []) {
            $this->info('No breaking changes detected.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->line('Compatibility Check Report');
        $this->line("Compared against: {$against}");
        $this->line('');

        $rows = [];
        foreach ($changes as $change) {
            $rows[] = [
                $change['contract'],
                $change['version'],
                strtoupper((string) $change['severity']),
                $change['message'],
            ];
        }

        $this->table(['Contract', 'Version', 'Severity', 'Change'], $rows);

        if ($report->hasBreakingChanges()) {
            $this->error('Breaking changes detected.');

            return 3;
        }

        if ($report->hasWarnings() && $this->option('fail-on-warning')) {
            $this->error('Warnings detected and --fail-on-warning is enabled.');

            return 4;
        }

        $this->warn('Warnings detected, but no breaking changes.');

        return self::SUCCESS;
    }
}
