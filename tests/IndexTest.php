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
use Chevere\Router\Endpoint;
use Chevere\Router\Index;
use Chevere\Router\Interfaces\IdentifierInterface;
use Chevere\Router\Path;
use Chevere\Router\Route;
use Chevere\String\Exceptions\CtypeSpaceException;
use Chevere\Tests\src\ControllerWithParameters;
use OutOfBoundsException;
use OverflowException;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\bind;
use function Chevere\Router\route;

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
        $this->expectException(CtypeSpaceException::class);
        $routerIndex->withAddedRoute($route, ' ');
    }

    public function testWithAddedRoute(): void
    {
        $groupName = '';
        $path = '/path/{id}/{name}';
        $route = route($path);
        $pathName = $route->path()->regex()->noDelimiters();
        $withEndpoint = $route->withEndpoint(
            new Endpoint(new GetMethod(), bind(ControllerWithParameters::class))
        );
        $this->assertNotSame($route, $withEndpoint);
        $index = new Index();
        $indexWithAddedRoute = $index
            ->withAddedRoute($withEndpoint, $groupName);
        $this->assertNotSame($index, $indexWithAddedRoute);
        $this->assertTrue($indexWithAddedRoute->hasRouteName($pathName));
        $this->assertInstanceOf(
            IdentifierInterface::class,
            $indexWithAddedRoute->getRouteIdentifier($pathName)
        );
        $this->assertTrue($indexWithAddedRoute->hasGroup($groupName));
        $this->assertSame(
            [$pathName],
            $indexWithAddedRoute->getGroupRouteNames($groupName)
        );
        $this->assertSame(
            $groupName,
            $indexWithAddedRoute->getRouteGroup($pathName)
        );
        $this->assertSame([
            $pathName => [
                'group' => $groupName,
                'name' => $pathName,
            ],
        ], $indexWithAddedRoute->toArray());
        $path2 = '/path-2/{id}/{name}';
        $route2 = route($path2);
        $path2Name = $route2->path()->regex()->noDelimiters();
        $route2 = $route2->withEndpoint(
            new Endpoint(new GetMethod(), bind(ControllerWithParameters::class))
        );
        $withAnotherAddedRoute = $indexWithAddedRoute->withAddedRoute($route2, $groupName);
        $this->assertSame(
            [$pathName, $path2Name],
            $withAnotherAddedRoute->getGroupRouteNames($groupName)
        );
        $this->assertCount(2, $withAnotherAddedRoute->toArray());
    }

    public function testWithAddedAlready(): void
    {
        $repo = 'repository';
        $route = route('/path/{id}/{name}')
            ->withEndpoint(
                new Endpoint(new GetMethod(), bind(ControllerWithParameters::class))
            );
        $routerIndex = (new Index())->withAddedRoute($route, $repo);
        $this->expectException(OverflowException::class);
        $routerIndex->withAddedRoute($route, 'other-group');
    }
}
