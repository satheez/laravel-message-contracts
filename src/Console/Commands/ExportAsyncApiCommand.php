<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Satheez\MessageContracts\AsyncApi\AsyncApiGenerator;
use Satheez\MessageContracts\JsonSchema\JsonSchemaGenerator;
use Satheez\MessageContracts\JsonSchema\LaravelRuleMapper;
use Symfony\Component\Yaml\Yaml;

class ExportAsyncApiCommand extends Command
{
    protected $signature = 'message-contracts:export-asyncapi
                            {--output= : Path where the AsyncAPI file should be written}
                            {--format=json : Output format (json or yaml)}';

    protected $description = 'Export registered message contracts as an AsyncAPI document.';

    public function handle(): int
    {
        $contracts = config('message-contracts.contracts', []);

        if (empty($contracts)) {
            $this->warn('No contracts registered.');

            return self::SUCCESS;
        }

        $formatArg = $this->option('format');
        $format = is_string($formatArg) ? strtolower($formatArg) : '';
        if (! in_array($format, ['json', 'yaml'], true)) {
            $this->error('Invalid format. Supported formats are: json, yaml');

            return 1;
        }

        $defaultPath = base_path("asyncapi.{$format}");
        $output = is_string($this->option('output')) ? $this->option('output') : $defaultPath;

        $this->info('Generating AsyncAPI document...');

        $generator = new AsyncApiGenerator(new JsonSchemaGenerator(new LaravelRuleMapper));
        $asyncapi = $generator->generate($contracts);

        $dir = dirname($output);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if ($format === 'yaml') {
            if (class_exists(Yaml::class)) {
                $content = Yaml::dump($asyncapi, 10, 2, Yaml::DUMP_OBJECT_AS_MAP);
            } else {
                $this->warn('The symfony/yaml package is not installed. Falling back to JSON format.');
                $content = (string) json_encode($asyncapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $output = (string) preg_replace('/\.yaml$/', '.json', $output);
            }
        } else {
            $content = (string) json_encode($asyncapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        File::put($output, $content);
        $this->info("AsyncAPI document saved to {$output}");

        return self::SUCCESS;
    }
}
