<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Satheez\MessageContracts\JsonSchema\JsonSchemaGenerator;
use Satheez\MessageContracts\JsonSchema\LaravelRuleMapper;

class ExportJsonSchemaCommand extends Command
{
    protected $signature = 'message-contracts:export-json-schema
                            {--output= : Directory where schemas should be written}
                            {--contract= : Export only one contract name}
                            {--contract-version= : Export only one version}
                            {--pretty : Pretty-print generated JSON}
                            {--include-message-envelope : Export full message schema instead of payload-only schema}
                            {--fail-on-warning : Fail if unsupported rules are found}';

    protected $description = 'Export registered message contracts as JSON Schema files.';

    public function handle(): int
    {
        if (! config('message-contracts.json_schema.enabled', true)) {
            $this->warn('JSON Schema export is disabled in config.');

            return self::SUCCESS;
        }

        $contracts = config('message-contracts.contracts', []);

        if (empty($contracts)) {
            $this->warn('No contracts registered.');

            return self::SUCCESS;
        }

        $outputDir = is_string($this->option('output')) ? $this->option('output') : config('message-contracts.json_schema.output_path', base_path('docs/schemas'));

        if (! File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $targetContract = $this->option('contract');
        $targetVersion = $this->option('contract-version');
        $pretty = $this->option('pretty') || config('message-contracts.json_schema.pretty', true);
        $includeEnvelope = (bool) $this->option('include-message-envelope');
        $failOnWarning = $this->option('fail-on-warning') || config('message-contracts.json_schema.fail_on_unsupported_rules', false);

        $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $jsonFlags |= JSON_PRETTY_PRINT;
        }

        $generator = new JsonSchemaGenerator(new LaravelRuleMapper);
        $hasWarnings = false;

        $this->info('Exporting JSON Schemas...');

        foreach ($contracts as $contractClass) {
            $name = $contractClass::contract();
            $version = (string) $contractClass::version();

            if ($targetContract && $name !== $targetContract) {
                continue;
            }

            if ($targetVersion && $version !== $targetVersion) {
                continue;
            }

            $result = $generator->generate($contractClass, $includeEnvelope);
            $warnings = $result['warnings'];

            if (! empty($warnings)) {
                $hasWarnings = true;
            }

            if ($hasWarnings && $failOnWarning) {
                $this->error('Schemas were not exported because warnings were found.');
                $this->printWarnings($name, $version, $warnings);

                return 4; // Exit code 4 as per docs
            }

            // Write payload schema
            $payloadSchemaPath = rtrim((string) $outputDir, '/')."/{$name}.v{$version}.schema.json";

            if ($includeEnvelope) {
                File::put($payloadSchemaPath, (string) json_encode($result['payload_schema'], $jsonFlags));

                $messageSchemaPath = rtrim((string) $outputDir, '/')."/{$name}.v{$version}.message.schema.json";
                File::put($messageSchemaPath, (string) json_encode($result['schema'], $jsonFlags));

                $outputMessage = "PASS     {$name}:v{$version} -> {$messageSchemaPath} & {$payloadSchemaPath}";
            } else {
                File::put($payloadSchemaPath, (string) json_encode($result['schema'], $jsonFlags));
                $outputMessage = "PASS     {$name}:v{$version} -> {$payloadSchemaPath}";
            }

            if (! empty($warnings)) {
                $this->warn(str_replace('PASS    ', 'WARNING ', $outputMessage));
                $this->printWarnings($name, $version, $warnings);
            } else {
                $this->line("<info>{$outputMessage}</info>");
            }
        }

        if ($hasWarnings && ! $failOnWarning) {
            $this->warn("\nSchemas exported with warnings.");
        }

        return self::SUCCESS;
    }

    private function printWarnings(string $contract, string $version, array $warnings): void
    {
        $this->line('');
        $this->warn("WARNING  {$contract}:v{$version}");
        foreach ($warnings as $warning) {
            $this->line("  - Field {$warning['field']} uses unsupported rule: {$warning['message']}");
        }
        $this->line('');
    }
}
