<?php

declare(strict_types=1);

namespace Satheez\MessageContracts;

use Illuminate\Support\ServiceProvider;
use Satheez\MessageContracts\Console\Commands\CheckCompatibilityCommand;
use Satheez\MessageContracts\Console\Commands\ExportAsyncApiCommand;
use Satheez\MessageContracts\Console\Commands\ExportJsonSchemaCommand;
use Satheez\MessageContracts\Console\Commands\ListMessageContractsCommand;
use Satheez\MessageContracts\Console\Commands\MakeMessageContractCommand;
use Satheez\MessageContracts\Console\Commands\SnapshotContractsCommand;
use Satheez\MessageContracts\Console\Commands\ValidateMessageCommand;
use Satheez\MessageContracts\Console\Commands\ValidateMessageExamplesCommand;
use Satheez\MessageContracts\Registry\MessageContractRegistry;
use Satheez\MessageContracts\Validation\MessageValidator;

class MessageContractsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/message-contracts.php',
            'message-contracts',
        );

        $this->app->singleton(MessageContractRegistry::class);
        $this->app->singleton(MessageValidator::class);
    }

    public function boot(): void
    {
        $this->publishConfig();
        $this->registerContracts();
        $this->registerCommands();
    }

    // ──────────────────────────────────────────────
    // Publishing
    // ──────────────────────────────────────────────

    private function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/message-contracts.php' => config_path('message-contracts.php'),
            ], 'message-contracts-config');

            $this->publishes([
                __DIR__.'/../resources/stubs' => base_path('stubs'),
            ], 'message-contracts-stubs');
        }
    }

    // ──────────────────────────────────────────────
    // Contract auto-registration from config
    // ──────────────────────────────────────────────

    private function registerContracts(): void
    {
        /** @var MessageContractRegistry $registry */
        $registry = $this->app->make(MessageContractRegistry::class);
        $contracts = config('message-contracts.contracts', []);

        foreach ($contracts as $contractClass) {
            $registry->register($contractClass);
        }
    }

    // ──────────────────────────────────────────────
    // Artisan commands
    // ──────────────────────────────────────────────

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeMessageContractCommand::class,
                ListMessageContractsCommand::class,
                ValidateMessageCommand::class,
                ValidateMessageExamplesCommand::class,
                ExportJsonSchemaCommand::class,
                SnapshotContractsCommand::class,
                CheckCompatibilityCommand::class,
                ExportAsyncApiCommand::class,
            ]);
        }
    }
}
