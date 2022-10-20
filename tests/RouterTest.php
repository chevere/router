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

use Chevere\Http\Methods\GetMethod;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\NotRoutableException;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use function Chevere\Router\route;
use Chevere\Router\Router;
use Chevere\Router\Tests\_resources\TestController;
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
        $controller = new TestController();
        $route = route('/🐘/{id:\d+}/{name:\w+}');
        $route = $route->withEndpoint(
            new Endpoint(
                new GetMethod(),
                $controller
            )
        );
        $router = new Router();
        $routerWithAddedRoute = $router
            ->withAddedRoute('my-group', $route);
        $this->assertNotSame($router, $routerWithAddedRoute);
        $this->assertCount(1, $routerWithAddedRoute->routes());
        $this->assertInstanceOf(
            RouteCollector::class,
            $routerWithAddedRoute->routeCollector()
        );
        $this->assertSame(
            [],
            $routerWithAddedRoute->routeCollector()->getData()[0]
        );
        $this->assertSame(
            [
                0 => $controller,
                1 => [
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            $routerWithAddedRoute->routeCollector()->getData()[1]['GET'][0]['routeMap'][3]
        );
    }

    public function testConstructInvalidArgument(): void
    {
        $route = route('/test');
        $this->expectException(WithoutEndpointsException::class);
        (new Router())
            ->withAddedRoute('my-group', $route);
    }

    public function testNotExportable(): void
    {
        $route = route('/test');
        $route->resource = fopen('php://output', 'r+');
        $this->expectException(NotRoutableException::class);
        (new Router())
            ->withAddedRoute('my-group', $route);
    }
}
