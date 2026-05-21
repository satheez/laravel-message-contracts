<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Compatibility;

use Illuminate\Support\Facades\File;
use Satheez\MessageContracts\JsonSchema\JsonSchemaGenerator;
use Satheez\MessageContracts\JsonSchema\LaravelRuleMapper;

class SnapshotManager
{
    public function generateSnapshot(array $contracts): array
    {
        $generator = new JsonSchemaGenerator(new LaravelRuleMapper);

        $snapshot = [
            'generated_at' => now()->toISOString(),
            'package' => 'satheez/laravel-payload-contracts',
            'contracts' => [],
        ];

        foreach ($contracts as $contractClass) {
            $name = $contractClass::contract();
            $version = $contractClass::version();

            $schemaResult = $generator->generate($contractClass, false);

            $snapshot['contracts'][] = [
                'contract' => $name,
                'version' => $version,
                'class' => $contractClass,
                'deprecated' => $contractClass::deprecated(),
                'rules' => method_exists($contractClass, 'rules') ? $contractClass::rules() : null,
                'schema' => $schemaResult['schema'],
            ];
        }

        return $snapshot;
    }

    public function save(string $path, array $snapshot): void
    {
        $dir = dirname($path);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($path, (string) json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function load(string $path): array
    {
        if (! File::exists($path)) {
            throw new \RuntimeException("Snapshot file not found at {$path}");
        }

        return json_decode(File::get($path), true);
    }
}
