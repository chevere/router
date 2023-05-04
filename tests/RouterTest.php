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

use Chevere\Http\Methods\GetMethod;
use function Chevere\Router\bind;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use function Chevere\Router\route;
use Chevere\Router\Router;
use Chevere\Tests\_resources\ControllerWithParameters;
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
        $controller = ControllerWithParameters::class;
        $bind = bind($controller);
        $route = route('/🐘/{id:\d+}/{name:\w+}');
        $route = $route->withEndpoint(
            new Endpoint(
                new GetMethod(),
                $bind
            )
        );
        $router = new Router();
        $routerWithAddedRoute = $router->withAddedRoute($route, 'my-group');
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
                0 => $bind,
                1 => [
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            $routerWithAddedRoute->routeCollector()
                ->getData()[1]['GET'][0]['routeMap'][3]
        );
    }

    public function testConstructInvalidArgument(): void
    {
        $route = route('/test');
        $this->expectException(WithoutEndpointsException::class);
        (new Router())->withAddedRoute($route, '');
    }
}
