<?php

declare(strict_types=1);

namespace InOtherAgents\Tests;

use InOtherAgents\AgentServiceProvider;
use OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelMcpServerServiceProvider::class,
            AgentServiceProvider::class,
        ];
    }
}
