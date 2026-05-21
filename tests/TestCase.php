<?php

declare(strict_types=1);

namespace Satheez\MessageContracts\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Satheez\MessageContracts\MessageContractsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MessageContractsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Defaults; individual tests may override via config()->set(...)
        $app['config']->set('message-contracts.strict', false);
        $app['config']->set('message-contracts.validate_outgoing', true);
        $app['config']->set('message-contracts.validate_incoming', true);
        $app['config']->set('message-contracts.meta.include_message_id', false);
        $app['config']->set('message-contracts.meta.include_created_at', false);
    }
}
