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
use Chevere\Http\Middlewares;
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
        $routesWithAdded = $routes->withAdded($route);
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
        $foo = (new Routes())->withAdded($routeFoo);
        $bar = (new Routes())->withAdded($routeBar);
        $fooWithEmpty = $foo->withRoutesFrom();
        $this->assertNotSame($foo, $fooWithEmpty);
        $fooWithBar = $foo->withRoutesFrom($bar);
        $barWithFoo = $bar->withRoutesFrom($foo);
        $this->assertNotSame($foo, $fooWithBar);
        $this->assertNotSame($bar, $barWithFoo);
        $this->assertNotSame($fooWithBar, $barWithFoo);
        $this->assertSame(['/test', '/test-2'], $fooWithBar->keys());
        $this->assertSame(['/test-2', '/test'], $barWithFoo->keys());
        $this->expectException(OverflowException::class);
        $foo->withRoutesFrom($foo);
    }

    // public function testWithMiddleware(): void
    // {
    //     $name = 'test';
    //     $route = (new Route(
    //         name: $name,
    //         path: new Path('/some-path')
    //     ));
    //     $foo = new MiddlewareOne();
    //     $bar = new MiddlewareTwo();
    //     $baz = new MiddlewareThree();
    //     $route = $route->withEndpoint(
    //         new Endpoint(
    //             new GetMethod(),
    //             new Bind(
    //                 (new ControllerNoParameters())->withMiddlewares(
    //                     new Middlewares($foo)
    //                 ),
    //                 'view'
    //             )
    //         )
    //     );

    //     $routes = (new Routes())->withAdded($route);
    //     $routesWith = $routes
    //         ->withAppendMiddleware($bar, $baz);
    //     $this->assertNotSame($routes, $routesWith);
    //     $middlewares = $routesWith->get('/some-path')->endpoints()->get('GET')
    //         ->bind()->controllerName()->middlewares();
    //     $this->assertSame([0, 1, 2], $middlewares->keys());
    //     $this->assertSame(
    //         [$foo, $bar, $baz],
    //         iterator_to_array($middlewares->getIterator())
    //     );
    //     $routesWith = $routes
    //         ->withPrependMiddleware($bar, $baz);
    //     $this->assertNotSame($routes, $routesWith);
    //     $middlewares = $routesWith->get('/some-path')->endpoints()->get('GET')
    //         ->bind()->controllerName()->middlewares();
    //     $this->assertSame([0, 1, 2], $middlewares->keys());
    //     $this->assertSame(
    //         [$bar, $baz, $foo],
    //         iterator_to_array($middlewares->getIterator())
    //     );
    // }

    public function testWithAddedNameCollision(): void
    {
        $name = 'test';
        $route = new Route(
            name: $name,
            path: new Path('/some-path')
        );
        $routes = (new Routes())->withAdded($route);
        $this->expectException(OverflowException::class);
        $this->expectExceptionCode(Routes::EXCEPTION_CODE_TAKEN_NAME);
        $routes->withAdded(
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
        $routes = (new Routes())->withAdded($route);
        $this->expectException(OverflowException::class);
        $this->expectExceptionCode(Routes::EXCEPTION_CODE_TAKEN_PATH);
        $routes->withAdded(
            new Route(
                name: 'test-2',
                path: $path
            )
        );
    }
}
