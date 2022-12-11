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
use Chevere\Router\Index;
use Chevere\Router\Interfaces\IdentifierInterface;
use Chevere\Router\Path;
use Chevere\Router\Route;
use function Chevere\Router\route;
use Chevere\Router\Tests\_resources\TestControllerWithParameters;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use PHPUnit\Framework\TestCase;

final class IndexTest extends TestCase
{
    public function testConstruct(): void
    {
        $routerIndex = new Index();
        $this->assertSame([], $routerIndex->toArray());
    }

    public function testGetRouteIdentifier(): void
    {
        $routerIndex = new Index();
        $this->expectException(OutOfBoundsException::class);
        $routerIndex->getRouteIdentifier('not-found');
    }

    public function testGetGroupRouteNames(): void
    {
        $routerIndex = new Index();
        $this->expectException(OutOfBoundsException::class);
        $routerIndex->getGroupRouteNames('not-found');
    }

    public function testGetRouteGroup(): void
    {
        $routerIndex = new Index();
        $this->expectException(OutOfBoundsException::class);
        $routerIndex->getRouteGroup('not-found');
    }

    public function testWithAddedRouteInvalidGroup(): void
    {
        $route = new Route(new Path('/'), 'test');
        $routerIndex = new Index();
        $this->expectException(InvalidArgumentException::class);
        $routerIndex->withAddedRoute($route, ' ');
    }

    public function testWithAddedRoute(): void
    {
        $groupName = 'some-group';
        $path = '/path';
        $route = route($path);
        $routeWithAddedEndpoint = $route->withEndpoint(
            new Endpoint(new GetMethod(), new TestControllerWithParameters())
        );
        $this->assertNotSame($route, $routeWithAddedEndpoint);
        $routerIndex = new Index();
        $routerIndexWithAddedRoute = $routerIndex
            ->withAddedRoute($routeWithAddedEndpoint, $groupName);
        $this->assertNotSame($routerIndex, $routerIndexWithAddedRoute);
        $this->assertTrue($routerIndexWithAddedRoute->hasRouteName($path));
        $this->assertInstanceOf(
            IdentifierInterface::class,
            $routerIndexWithAddedRoute->getRouteIdentifier($path)
        );
        $this->assertTrue($routerIndexWithAddedRoute->hasGroup($groupName));
        $this->assertSame(
            [$path],
            $routerIndexWithAddedRoute->getGroupRouteNames($groupName)
        );
        $this->assertSame(
            $groupName,
            $routerIndexWithAddedRoute->getRouteGroup($path)
        );
        $this->assertSame([
            $path => [
                'group' => $groupName,
                'name' => $path,
            ],
        ], $routerIndexWithAddedRoute->toArray());
        $path2 = '/path-2';
        $route2 = route($path2);
        $route2 = $route2->withEndpoint(
            new Endpoint(new GetMethod(), new TestControllerWithParameters())
        );
        $withAnotherAddedRoute = $routerIndexWithAddedRoute->withAddedRoute($route2, $groupName);
        $this->assertSame(
            [$path, $path2],
            $withAnotherAddedRoute->getGroupRouteNames($groupName)
        );
        $this->assertCount(2, $withAnotherAddedRoute->toArray());
    }

    public function testWithAddedAlready(): void
    {
        $repo = 'repository';
        $route = route('/path')
            ->withEndpoint(
                new Endpoint(new GetMethod(), new TestControllerWithParameters())
            );
        $routerIndex = (new Index())->withAddedRoute($route, $repo);
        $this->expectException(OverflowException::class);
        $routerIndex->withAddedRoute($route, 'other-group');
    }
}
