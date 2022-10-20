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
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\WildcardConflictException;
use Chevere\Router\Path;
use function Chevere\Router\route;
use Chevere\Router\Route;
use Chevere\Router\Tests\_resources\MiddlewareOne;
use Chevere\Router\Tests\_resources\MiddlewareThree;
use Chevere\Router\Tests\_resources\MiddlewareTwo;
use Chevere\Router\Tests\_resources\RouteTestController;
use Chevere\Router\Tests\_resources\RouteTestControllerNoParams;
use Chevere\Router\Tests\_resources\RouteTestControllerRegexConflict;
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
        $this->assertSame('', $route->view());
        $route = route($path, 'name', 'view');
        $this->assertSame('name', $route->name());
        $this->assertEquals($routePath, $route->path());
        $this->assertSame('view', $route->view());
    }

    public function testWithAddedEndpoint(): void
    {
        $route = new Route(new Path('/test'), 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, $controller);
        $route = $route->withEndpoint($endpoint);
        $this->assertTrue($route->endpoints()->hasKey($method->name()));
        $this->assertSame($endpoint, $route->endpoints()->get($method->name()));
    }

    public function testWithAddedEndpointWrongWildcard(): void
    {
        $route = new Route(new Path('/test/{foo}'), 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, $controller);
        $this->expectException(OutOfBoundsException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointNoParams(): void
    {
        $route = new Route(new Path('/test/{foo}'), 'test');
        $method = new GetMethod();
        $controller = new RouteTestControllerNoParams();
        $endpoint = new Endpoint($method, $controller);
        $this->expectException(InvalidArgumentException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointWildcardMissing(): void
    {
        $parameter = 'int';
        $path = new Path('/test/{' . $parameter . ':[0-9]+}');
        $endpoint = new Endpoint(
            new GetMethod(),
            new RouteTestController()
        );
        $controllerName = RouteTestController::class;
        $route = new Route($path, 'test');
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage(<<<PLAIN
        Wildcard parameter {$parameter} must bind to one of the known {$controllerName} parameters
        PLAIN);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointWildcardParameter(): void
    {
        $path = new Path('/test/{id:[0-9]+}');
        $route = new Route($path, 'test');
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, $controller);
        $route = $route->withEndpoint($endpoint);
        $this->assertTrue($route->endpoints()->hasKey($method->name()));
        $this->assertSame(
            [],
            $route->endpoints()->get($method->name())->parameters()
        );
    }

    public function testWithAddedEndpointOverride(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $endpoint = new Endpoint(new GetMethod(), new RouteTestController());
        $route = $route->withEndpoint($endpoint);
        $this->expectException(OverflowException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithAddedEndpointConflict(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $endpoint1 = new Endpoint(new GetMethod(), new RouteTestController());
        $endpoint2 = new Endpoint(new PostMethod(), new RouteTestControllerRegexConflict());
        $route = $route->withEndpoint($endpoint1);
        $this->expectException(EndpointConflictException::class);
        $route->withEndpoint($endpoint2);
    }

    public function testWithAddedEndpointWildcardConflict(): void
    {
        $route = new Route(new Path('/test/{id:\w+}'), 'test');
        $endpoint = new Endpoint(new GetMethod(), new RouteTestController());
        $this->expectException(WildcardConflictException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithMiddleware(): void
    {
        $route = route('/test/{id:\w+}');
        $this->assertSame([], $route->middleware());
        $middlewareOne = new MiddlewareOne();
        $middlewareTwo = new MiddlewareTwo();
        $middlewareThree = new MiddlewareThree();
        $middleware = [$middlewareOne, $middlewareTwo];
        $middlewareMore = [$middlewareTwo, $middlewareThree];
        $routeWithMiddleware = $route
            ->withMiddleware(...$middleware);
        $this->assertNotSame($route, $routeWithMiddleware);
        $this->assertSame($middleware, $routeWithMiddleware->middleware());
        $routeWithMiddleware = $routeWithMiddleware
            ->withMiddleware(...$middlewareMore);
        $this->assertSame(
            [$middlewareOne, $middlewareTwo, $middlewareThree],
            $routeWithMiddleware->middleware()
        );
    }
}
