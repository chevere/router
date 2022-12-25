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
use Chevere\Http\Methods\PostMethod;
use function Chevere\Router\bind;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\WildcardConflictException;
use Chevere\Router\Path;
use function Chevere\Router\route;
use Chevere\Router\Route;
use Chevere\Router\Tests\_resources\RouteTestController;
use Chevere\Router\Tests\_resources\RouteTestControllerRegexConflict;
use Chevere\Router\Tests\_resources\TestControllerNoParameters;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testConstruct(): void
    {
        $path = '/test';
        $routePath = new Path($path);
        $route = new Route($routePath, 'test');
        $this->assertSame($routePath, $route->path());
        $route = route($path, 'name', 'view');
        $this->assertSame('name', $route->name());
        $this->assertEquals($routePath, $route->path());
    }

    public function testWithAddedEndpoint(): void
    {
        $route = new Route(new Path('/test'), 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, bind($controller));
        $route = $route->withEndpoint($endpoint);
        $this->assertTrue($route->endpoints()->has($method->name()));
        $this->assertSame($endpoint, $route->endpoints()->get($method->name()));
    }

    public function testWithWildcard(): void
    {
        $route = new Route(new Path('/test/{id}'), 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectNotToPerformAssertions();
        $route->withEndpoint($endpoint);
    }

    public function testWithWildcardStrict(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectNotToPerformAssertions();
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointWrongWildcard(): void
    {
        $route = new Route(new Path('/test/{foo}'), 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectException(OutOfBoundsException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointNoParams(): void
    {
        $route = new Route(new Path('/test/{foo}'), 'test');
        $method = new GetMethod();
        $controller = new TestControllerNoParameters();
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectException(InvalidArgumentException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointWildcardMissing(): void
    {
        $parameter = 'int';
        $path = new Path('/test/{' . $parameter . ':[0-9]+}');
        $pathString = strval($path);
        $controller = new RouteTestController();
        $endpoint = new Endpoint(
            new GetMethod(),
            bind($controller)
        );
        $controllerName = RouteTestController::class;
        $parameterMissing = $controller->parameters()->keys()[0];
        $route = new Route($path, 'test');
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage(<<<PLAIN
        Route {$pathString} must bind to one of the known {$controllerName} parameters: {$parameterMissing}
        PLAIN);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointWildcardParameter(): void
    {
        $path = new Path('/test/{id:[0-9]+}');
        $route = new Route($path, 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, bind($controller));
        $route = $route->withEndpoint($endpoint);
        $this->assertTrue($route->endpoints()->has($method->name()));
        $this->assertSame(
            [],
            $route->endpoints()->get($method->name())->parameters()
        );
    }

    public function testWithAddedEndpointOverride(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $endpoint = new Endpoint(new GetMethod(), bind(new RouteTestController()));
        $route = $route->withEndpoint($endpoint);
        $this->expectException(OverflowException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointConflict(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $endpoint1 = new Endpoint(new GetMethod(), bind(new RouteTestController()));
        $endpoint2 = new Endpoint(new PostMethod(), bind(new RouteTestControllerRegexConflict()));
        $route = $route->withEndpoint($endpoint1);
        $this->expectException(EndpointConflictException::class);
        $route->withEndpoint($endpoint2);
    }

    public function testWithAddedEndpointWildcardConflict(): void
    {
        $route = new Route(new Path('/test/{id:\w+}'), 'test');
        $endpoint = new Endpoint(
            new GetMethod(),
            bind(new RouteTestController())
        );
        $this->expectException(WildcardConflictException::class);
        $this->expectExceptionMessage('Wildcard {id} matches against');
        $route->withEndpoint($endpoint);
    }
}
