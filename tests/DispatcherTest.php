<?php

/*
 * This file is part of Chevere.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Chevere\Tests;

use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Router\Dispatcher;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Parsers\StrictStd;
use Chevere\Tests\src\ControllerWithParameters;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\bind;

final class DispatcherTest extends TestCase
{
    public function testNotFound(): void
    {
        $routeDispatcher = new Dispatcher($this->getRouteCollector());
        $this->expectException(NotFoundException::class);
        $request = new ServerRequest('GET', '/');
        $routeDispatcher->dispatch($request);
    }

    public function testFound(): void
    {
        $routeCollector = $this->getRouteCollector();
        $bind = bind(ControllerWithParameters::class);
        $routeCollector->addRoute('GET', '/', $bind);
        $routeDispatcher = new Dispatcher($routeCollector);
        $request = new ServerRequest('GET', '/');
        $bindDispatch = $routeDispatcher->dispatch($request)->bind();
        $this->assertSame($bind, $bindDispatch);
    }

    public function testMatch(): void
    {
        $routeCollector = $this->getRouteCollector();
        $bind = bind(ControllerWithParameters::class);
        $routeCollector->addRoute('GET', '/apps', $bind);
        $routeCollector->addRoute('GET', '/apps/{id}', $bind);
        $routeDispatcher = new Dispatcher($routeCollector);
        $request = new ServerRequest('GET', '/apps/js');
        $bindDispatch = $routeDispatcher->dispatch($request)->bind();
        $this->assertSame($bind, $bindDispatch);
    }

    public function testHttpMethodNotAllowed(): void
    {
        $routeCollector = $this->getRouteCollector();
        $routeCollector->addRoute('GET', '/', 'test');
        $routeDispatcher = new Dispatcher($routeCollector);
        $this->expectException(MethodNotAllowedException::class);
        $request = new ServerRequest('Asdf', '/');
        $routeDispatcher->dispatch($request);
    }

    private function getRouteCollector(): RouteCollector
    {
        return new RouteCollector(new StrictStd(), new GroupCountBased());
    }
}
