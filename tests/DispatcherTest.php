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
use PHPUnit\Framework\TestCase;
use function Chevere\Router\bind;

final class DispatcherTest extends TestCase
{
    public function testNotFound(): void
    {
        $routeDispatcher = new Dispatcher($this->getRouteCollector());
        $this->expectException(NotFoundException::class);
        $routeDispatcher->dispatch('get', '/');
    }

    public function testFound(): void
    {
        $routeCollector = $this->getRouteCollector();
        $bind = bind(ControllerWithParameters::class);
        $routeCollector->addRoute('GET', '/', $bind);
        $routeDispatcher = new Dispatcher($routeCollector);
        $bindDispatch = $routeDispatcher->dispatch('GET', '/')->bind();
        $this->assertSame($bind, $bindDispatch);
    }

    public function testHttpMethodNotAllowed(): void
    {
        $routeCollector = $this->getRouteCollector();
        $routeCollector->addRoute('GET', '/', 'test');
        $routeDispatcher = new Dispatcher($routeCollector);
        $this->expectException(MethodNotAllowedException::class);
        $routeDispatcher->dispatch('Asdf', '/');
    }

    private function getRouteCollector(): RouteCollector
    {
        return new RouteCollector(new StrictStd(), new GroupCountBased());
    }
}
