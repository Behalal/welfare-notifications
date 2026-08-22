<?php

namespace Welfare\Notifications\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\NullLogger;
use Welfare\Notifications\NotificationDispatcher;
use Welfare\Notifications\Push\PushManager;
use Welfare\Notifications\Sms\SmsManager;

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

    /**
     * Build a real NotificationDispatcher wired to a given (fake) Bus
     * dispatcher, backed by the same config array's sms/push driver
     * selection. Events go to a real Illuminate\Events\Dispatcher so tests
     * can assert on NotificationDispatched via a listener if needed.
     */
    protected function makeDispatcher(BusDispatcher $bus, array $config = []): NotificationDispatcher
    {
        $container = $this->makeContainer($config);

        return new NotificationDispatcher(
            $bus,
            new SmsManager($container),
            new EventDispatcher(),
        );
    }
}
