<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery as m;
use Recca0120\LaravelTracy\ServiceProvider;
use Recca0120\LaravelTracy\Tracy;

class ServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_register()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $app = m::mock('Illuminate\Contracts\Foundation\Application, ArrayAccess');
        $request = m::mock('Illuminate\Http\Request');
        $session = m::mock('Illuminate\Session\SessionManager');
        $sessionHandler = m::mock('SessionHandlerInterface');
        $config = m::mock('stdClass');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $config
            ->shouldReceive('get')->with('tracy', [])->andReturn(['useLaravelSession' => true])
            ->shouldReceive('set')
            ->shouldReceive('get')->with('tracy.panels.terminal')->andReturn(true);

        $request->shouldReceive('ajax')->andReturn(false);

        $session->shouldReceive('driver->getHandler')->once()->andReturn($sessionHandler);

        $app
            ->shouldReceive('offsetGet')->with('config')->andReturn($config)
            ->shouldReceive('offsetGet')->with('request')->andReturn($request)
            ->shouldReceive('offsetGet')->with('session')->andReturn($session)
            ->shouldReceive('singleton')->with('Recca0120\LaravelTracy\Tracy', m::type('Closure'))->andReturnUsing(function ($className, $closure) use ($app) {
                return $closure($app);
            })
            ->shouldReceive('singleton')->with('Recca0120\LaravelTracy\Debugbar', m::type('Closure'))->once()->andReturnUsing(function ($className, $closure) use ($app) {
                return $closure($app);
            })
            ->shouldReceive('singleton')->with('Recca0120\LaravelTracy\BlueScreen', 'Recca0120\LaravelTracy\BlueScreen')->once()
            ->shouldReceive('register')->with('Recca0120\Terminal\ServiceProvider')->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $serviceProvider = new ServiceProvider($app);
        $serviceProvider->register();
        $serviceProvider->provides();
    }

    public function test_boot()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $app = m::mock('Illuminate\Contracts\Foundation\Application, ArrayAccess');
        $tracy = m::mock('Recca0120\LaravelTracy\Tracy');
        $kernel = m::mock('Illuminate\Contracts\Http\Kernel');
        $handler = m::mock('Illuminate\Contracts\Debug\ExceptionHandler');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $app
            ->shouldReceive('configPath')->andReturn(__DIR__)
            ->shouldReceive('extend')->with('Illuminate\Contracts\Debug\ExceptionHandler', m::type('Closure'))->andReturnUsing(function ($className, $closure) use ($handler, $app) {
                return $closure($handler, $app);
            })
            ->shouldReceive('make')->with('Recca0120\LaravelTracy\Exceptions\Handler', [
                'exceptionHandler' => $handler,
            ]);

        $tracy->shouldReceive('enable')->andReturn(true);

        $kernel
            ->shouldReceive('prependMiddleware')->with('Recca0120\LaravelTracy\Middleware\Dispatch')->once()
            ->shouldReceive('pushMiddleware')->with('Recca0120\LaravelTracy\Middleware\AppendDebugbar')->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $serviceProvider = new ServiceProvider($app);
        $serviceProvider->boot($tracy, $kernel);
    }
}
