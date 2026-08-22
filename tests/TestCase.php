<?php

namespace Welfare\Notifications\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\NullLogger;

/**
 * Deliberately not orchestra/testbench — a plain Manager subclass only
 * needs $container->make('config') to work (see Illuminate\Support\Manager),
 * so a bare Illuminate\Container\Container with a config repository bound
 * in is enough. Keeps the package's dev dependencies (and CI time) small.
 *
 * Drivers log via the Log:: facade, which needs a facade root — bound here
 * to a PSR NullLogger so tests don't need the full illuminate/log stack.
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Facade root for Log:: — see class docblock.
        $this->makeContainer();
    }

    protected function makeContainer(array $config = []): Container
    {
        $container = new Container();
        $container->instance('config', new Repository($config));
        $container->instance('log', new NullLogger());

        Facade::setFacadeApplication($container);

        return $container;
    }
}
