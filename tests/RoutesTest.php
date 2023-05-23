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
use Chevere\Http\MiddlewareName;
use Chevere\Http\Middlewares;
use function Chevere\Http\middlewares;
use Chevere\HttpController\HttpControllerName;
use Chevere\Router\Bind;
use Chevere\Router\Endpoint;
use Chevere\Router\Path;
use Chevere\Router\Route;
use Chevere\Router\Routes;
use Chevere\Tests\_resources\ControllerNoParameters;
use Chevere\Tests\_resources\MiddlewareOne;
use Chevere\Tests\_resources\MiddlewareThree;
use Chevere\Tests\_resources\MiddlewareTwo;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use PHPUnit\Framework\TestCase;

final class RoutesTest extends TestCase
{
    public function testWithAdded(): void
    {
        $name = 'test';
        $route = (new Route(
            name: $name,
            path: new Path('/some-path')
        ));
        $key = $route->path()->regex()->noDelimiters();
        $routes = new Routes();
        $routesWithAdded = $routes->withRoute($route);
        $this->assertNotSame($routes, $routesWithAdded);
        $this->assertTrue($routesWithAdded->has($key));
        $this->assertSame($route, $routesWithAdded->get($key));
        $this->expectException(OutOfBoundsException::class);
        $routesWithAdded->get('404');
    }

    public function testWithRoutesFrom(): void
    {
        $routeFoo = (new Route(
            new Path('/test'),
            'test'
        ));
        $routeBar = (new Route(
            new Path('/test-2'),
            'test-2'
        ));
        $foo = (new Routes())->withRoute($routeFoo);
        $bar = (new Routes())->withRoute($routeBar);
        $fooWithEmpty = $foo->withRoutes();
        $this->assertNotSame($foo, $fooWithEmpty);
        $fooWithBar = $foo->withRoutes($bar);
        $barWithFoo = $bar->withRoutes($foo);
        $this->assertNotSame($foo, $fooWithBar);
        $this->assertNotSame($bar, $barWithFoo);
        $this->assertNotSame($fooWithBar, $barWithFoo);
        $this->assertSame(['/test', '/test-2'], $fooWithBar->keys());
        $this->assertSame(['/test-2', '/test'], $barWithFoo->keys());
        $this->expectException(OverflowException::class);
        $foo->withRoutes($foo);
    }

    public function testWithMiddlewares(): void
    {
        $name = 'test';
        $route = (new Route(
            name: $name,
            path: new Path('/some-path')
        ));
        $one = new MiddlewareName(MiddlewareOne::class);
        $two = new MiddlewareName(MiddlewareTwo::class);
        $three = new MiddlewareName(MiddlewareThree::class);
        $controllerName = new HttpControllerName(ControllerNoParameters::class);
        $middlewares = new Middlewares($one);
        $endpoint = new Endpoint(
            new GetMethod(),
            new Bind($controllerName, $middlewares, 'view')
        );
        $route = $route->withEndpoint($endpoint);
        $routes = (new Routes())->withRoute($route);
        $routesWith = $routes->withAppendMiddleware(
            middlewares(MiddlewareTwo::class, MiddlewareThree::class)
        );
        $this->assertNotSame($routes, $routesWith);
        $middlewares = $routesWith->get('/some-path')->endpoints()->get('GET')->bind()->middlewares();
        $this->assertSame([0, 1, 2], $middlewares->keys());
        $this->assertEquals(
            [$one, $two, $three],
            iterator_to_array($middlewares)
        );
        $routesWith = $routes->withPrependMiddleware(
            middlewares(MiddlewareTwo::class, MiddlewareThree::class)
        );
        $this->assertNotSame($routes, $routesWith);
        $middlewares = $routesWith->get('/some-path')->endpoints()->get('GET')->bind()->middlewares();
        $this->assertSame([0, 1, 2], $middlewares->keys());
        $this->assertEquals(
            [$two, $three, $one],
            iterator_to_array($middlewares)
        );
    }

    public function testWithAddedNameCollision(): void
    {
        $name = 'test';
        $route = new Route(
            name: $name,
            path: new Path('/some-path')
        );
        $routes = (new Routes())->withRoute($route);
        $this->expectException(OverflowException::class);
        $this->expectExceptionCode(Routes::EXCEPTION_CODE_TAKEN_NAME);
        $routes->withRoute(
            new Route(
                name: $name,
                path: new Path('/some-alt-path')
            )
        );
    }

    public function testWithAddedPathCollision(): void
    {
        $path = new Path('/some-path');
        $route = new Route(
            name: 'test',
            path: $path
        );
        $routes = (new Routes())->withRoute($route);
        $this->expectException(OverflowException::class);
        $this->expectExceptionCode(Routes::EXCEPTION_CODE_TAKEN_PATH);
        $routes->withRoute(
            new Route(
                name: 'test-2',
                path: $path
            )
        );
    }
}
