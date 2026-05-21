<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Compatibility;

class SchemaComparator
{
    public function compare(array $previousSnapshot, array $currentSnapshot): CompatibilityReport
    {
        $report = new CompatibilityReport;

        $previousContracts = $this->indexContracts($previousSnapshot['contracts'] ?? []);
        $currentContracts = $this->indexContracts($currentSnapshot['contracts'] ?? []);

        foreach ($previousContracts as $key => $prevContract) {
            if (! isset($currentContracts[$key])) {
                $report->addBreaking(
                    $prevContract['contract'],
                    (string) $prevContract['version'],
                    'Contract version was removed or renamed.'
                );

                continue;
            }

            $currContract = $currentContracts[$key];

            if (! $prevContract['deprecated'] && $currContract['deprecated']) {
                $report->addWarning(
                    $currContract['contract'],
                    (string) $currContract['version'],
                    'Contract was marked as deprecated.'
                );
            }

            $this->compareSchemas(
                $currContract['contract'],
                (string) $currContract['version'],
                $prevContract['schema'] ?? [],
                $currContract['schema'] ?? [],
                $report
            );
        }

        return $report;
    }

    private function indexContracts(array $contracts): array
    {
        $indexed = [];
        foreach ($contracts as $contract) {
            $key = $contract['contract'].':'.$contract['version'];
            $indexed[$key] = $contract;
        }

        return $indexed;
    }

    private function compareSchemas(string $contract, string $version, array $prevSchema, array $currSchema, CompatibilityReport $report, string $path = ''): void
    {
        $prevProperties = $prevSchema['properties'] ?? [];
        $currProperties = $currSchema['properties'] ?? [];

        $prevRequired = $prevSchema['required'] ?? [];
        $currRequired = $currSchema['required'] ?? [];

        // Check for removed properties
        foreach ($prevProperties as $prop => $prevPropSchema) {
            $propPath = $path === '' ? $prop : "{$path}.{$prop}";
            if (! array_key_exists($prop, $currProperties)) {
                if (in_array($prop, $prevRequired, true)) {
                    $report->addBreaking($contract, $version, "Removed required field: {$propPath}", $propPath);
                } else {
                    $report->addBreaking($contract, $version, "Removed optional field: {$propPath}", $propPath);
                }

                continue;
            }

            // Compare types
            $prevType = $prevPropSchema['type'] ?? null;
            $currType = $currProperties[$prop]['type'] ?? null;

            if ($this->isTypeNarrowed($prevType, $currType)) {
                $report->addBreaking($contract, $version, 'Field type narrowed from '.json_encode($prevType).' to '.json_encode($currType), $propPath);
            } elseif ($prevType !== $currType && ! $this->isTypeExpanded($prevType, $currType)) {
                $report->addBreaking($contract, $version, 'Field type changed from '.json_encode($prevType).' to '.json_encode($currType), $propPath);
            }

            // Enum changes
            $prevEnum = $prevPropSchema['enum'] ?? null;
            $currEnum = $currProperties[$prop]['enum'] ?? null;
            if ($prevEnum && ! $currEnum) {
                $report->addSafe($contract, $version, "Enum constraints removed on field: {$propPath}", $propPath);
            } elseif ($prevEnum && $currEnum) {
                $removedEnums = array_diff($prevEnum, $currEnum);
                $addedEnums = array_diff($currEnum, $prevEnum);
                if ($removedEnums !== []) {
                    $report->addBreaking($contract, $version, 'Enum value(s) removed: '.implode(', ', $removedEnums), $propPath);
                }
                if ($addedEnums !== []) {
                    $report->addWarning($contract, $version, 'Enum value(s) added: '.implode(', ', $addedEnums), $propPath);
                }
            }

            // Recursion for objects
            if ($this->hasType($currType, 'object')) {
                $this->compareSchemas($contract, $version, $prevPropSchema, $currProperties[$prop], $report, $propPath);
            }

            // Recursion for arrays
            if ($this->hasType($currType, 'array') && isset($prevPropSchema['items'], $currProperties[$prop]['items'])) {
                $this->compareSchemas($contract, $version, $prevPropSchema['items'], $currProperties[$prop]['items'], $report, "{$propPath}[]");
            }
        }

        // Check for added required properties
        foreach ($currRequired as $req) {
            if (! in_array($req, $prevRequired, true)) {
                $propPath = $path === '' ? $req : "{$path}.{$req}";
                if (! array_key_exists($req, $prevProperties)) {
                    $report->addBreaking($contract, $version, "Added new required field: {$propPath}", $propPath);
                } else {
                    $report->addBreaking($contract, $version, "Optional field made required: {$propPath}", $propPath);
                }
            }
        }

        // Check for added optional properties
        foreach ($currProperties as $prop => $currPropSchema) {
            if (! array_key_exists($prop, $prevProperties)) {
                $propPath = $path === '' ? $prop : "{$path}.{$prop}";
                if (! in_array($prop, $currRequired, true)) {
                    $report->addSafe($contract, $version, "Added optional field: {$propPath}", $propPath);
                }
            }
        }
    }

    private function hasType(mixed $type, string $target): bool
    {
        if (is_string($type)) {
            return $type === $target;
        }
        if (is_array($type)) {
            return in_array($target, $type, true);
        }

        return false;
    }

    private function isTypeNarrowed(mixed $prev, mixed $curr): bool
    {
        if ($prev === null || $curr === null) {
            return false;
        }
        $prevArr = (array) $prev;
        $currArr = (array) $curr;
        $diff = array_diff($prevArr, $currArr);

        return $diff !== [] && count($prevArr) > count($currArr);
    }

    private function isTypeExpanded(mixed $prev, mixed $curr): bool
    {
        if ($prev === null || $curr === null) {
            return false;
        }
        $prevArr = (array) $prev;
        $currArr = (array) $curr;

        return array_diff($prevArr, $currArr) === [];
    }
}
