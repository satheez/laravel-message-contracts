<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Compatibility;

class CompatibilityReport
{
    private array $changes = [];

    public function addSafe(string $contract, string $version, string $message, ?string $field = null): void
    {
        $this->addChange($contract, $version, 'safe', $message, $field);
    }

    public function addWarning(string $contract, string $version, string $message, ?string $field = null): void
    {
        $this->addChange($contract, $version, 'warning', $message, $field);
    }

    public function addBreaking(string $contract, string $version, string $message, ?string $field = null): void
    {
        $this->addChange($contract, $version, 'breaking', $message, $field);
    }

    private function addChange(string $contract, string $version, string $severity, string $message, ?string $field): void
    {
        $this->changes[] = [
            'contract' => $contract,
            'version' => $version,
            'severity' => $severity,
            'field' => $field,
            'message' => $message,
        ];
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function hasBreakingChanges(): bool
    {
        foreach ($this->changes as $change) {
            if ($change['severity'] === 'breaking') {
                return true;
            }
        }

        return false;
    }

    public function hasWarnings(): bool
    {
        foreach ($this->changes as $change) {
            if ($change['severity'] === 'warning') {
                return true;
            }
        }

        return false;
    }
}
