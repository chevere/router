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

namespace Chevere\Tests\Router;

use Chevere\Http\Methods\GetMethod;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\NotRoutableException;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Path;
use Chevere\Router\Route;
use Chevere\Router\Router;
use Chevere\Tests\Router\_resources\src\TestController;
use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testConstruct(): void
    {
        $router = new Router();
        $this->assertSame([], $router->index()->toArray());
        $this->assertCount(0, $router->routes());
    }

    public function testRouter(): void
    {
        $routePath = new Path('/🐘/{id:\d+}/{name:\w+}');
        $route = new Route('test', $routePath);
        $route = $route->withAddedEndpoint(
            new Endpoint(
                new GetMethod(),
                new TestController()
            )
        );
        $router = new Router();
        $routerWithAddedRoute = $router
            ->withAddedRoute(route: $route, group: 'my-group');
        $this->assertNotSame($router, $routerWithAddedRoute);
        $this->assertCount(1, $routerWithAddedRoute->routes());
        $this->assertInstanceOf(
            RouteCollector::class,
            $routerWithAddedRoute->routeCollector()
        );
    }

    public function testConstructInvalidArgument(): void
    {
        $route = new Route('test', new Path('/test'));
        $this->expectException(WithoutEndpointsException::class);
        (new Router())
            ->withAddedRoute(route: $route, group: 'my-group');
    }

    public function testNotExportable(): void
    {
        $route = new Route('test', new Path('/test'));
        $route->resource = fopen('php://output', 'r+');
        $this->expectException(NotRoutableException::class);
        (new Router())
            ->withAddedRoute(route: $route, group: 'my-group');
    }
}
