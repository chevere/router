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

use Chevere\Router\Path;
use Chevere\Router\Route;
use Chevere\Router\Routes;
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
        $key = $route->path()->__toString();
        $routes = new Routes();
        $routesWithAdded = $routes
            ->withAdded($route);
        $this->assertNotSame($routes, $routesWithAdded);
        $this->assertTrue($routesWithAdded->has($key));
        $this->assertSame($route, $routesWithAdded->get($key));
        $this->expectException(OutOfBoundsException::class);
        $routesWithAdded->get('404');
    }

    public function testWithAddedNameCollision(): void
    {
        $name = 'test';
        $route = new Route(
            name: $name,
            path: new Path('/some-path')
        );
        $key = $route->path()->__toString();
        $routes = (new Routes())
            ->withAdded($route);
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
        $key = $route->path()->__toString();
        $routes = (new Routes())
            ->withAdded($route);
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
