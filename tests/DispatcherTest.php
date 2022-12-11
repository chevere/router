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

namespace Chevere\Router\Tests;

use Chevere\Http\Exceptions\HttpMethodNotAllowedException;
use Chevere\Router\Dispatcher;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Parsers\StrictStd;
use Chevere\Router\Tests\_resources\TestControllerWithParameters;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;

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
        $routeCollector->addRoute('GET', '/', new TestControllerWithParameters());
        $routeDispatcher = new Dispatcher($routeCollector);
        $this->expectNotToPerformAssertions();
        $routeDispatcher->dispatch('GET', '/');
    }

    public function testHttpMethodNotAllowed(): void
    {
        $routeCollector = $this->getRouteCollector();
        $routeCollector->addRoute('GET', '/', function () {
        });
        $routeDispatcher = new Dispatcher($routeCollector);
        $this->expectException(HttpMethodNotAllowedException::class);
        $routeDispatcher->dispatch('Asdf', '/');
    }

    private function getRouteCollector(): RouteCollector
    {
        return new RouteCollector(new StrictStd(), new GroupCountBased());
    }
}
