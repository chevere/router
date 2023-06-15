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
use Chevere\Http\Methods\PostMethod;
use function Chevere\Router\bind;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\VariableConflictException;
use Chevere\Router\Path;
use function Chevere\Router\route;
use Chevere\Router\Route;
use Chevere\Tests\_resources\ControllerNoParameters;
use Chevere\Tests\_resources\ControllerRegexConflict;
use Chevere\Tests\_resources\ControllerWithParameter;
use Chevere\Tests\_resources\ControllerWithParameters;
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
        $route = route($path, 'name');
        $this->assertSame('name', $route->name());
        $this->assertEquals($routePath, $route->path());
        $this->expectException(OutOfBoundsException::class);
        $route->withoutEndpoint(new GetMethod());
    }

    public function testWithEndpoint(): void
    {
        $route = new Route(new Path('/test/{id}'), 'test');
        $method = new GetMethod();
        $controller = ControllerWithParameter::class;
        $endpoint = new Endpoint($method, bind($controller));
        $routeWith = $route->withEndpoint($endpoint);
        $this->assertTrue($routeWith->endpoints()->has($method->name()));
        $this->assertSame($endpoint, $routeWith->endpoints()->get($method->name()));
    }

    public function testWithoutEndpointFixFirst(): void
    {
        $route = new Route(new Path('/test/{id}'), 'test');
        $fooMethod = new GetMethod();
        $barMethod = new PostMethod();
        $controller = ControllerWithParameter::class;
        $foo = new Endpoint($fooMethod, bind($controller));
        $bar = new Endpoint($barMethod, bind($controller));
        $route = $route
            ->withEndpoint($foo)
            ->withEndpoint($bar);
        $withoutFoo = $route->withoutEndpoint($fooMethod);
        $this->assertFalse($withoutFoo->endpoints()->has($fooMethod->name()));
    }

    public function testWithoutEndpointOutOfBounds(): void
    {
        $route = new Route(new Path('/test'), 'test');
        $method = new GetMethod();
        $controller = ControllerWithParameter::class;
        $foo = new Endpoint($method, bind($controller));
        $this->expectException(OutOfBoundsException::class);
        $route->withoutEndpoint($method);
    }

    public function testWithVariable(): void
    {
        $route = new Route(new Path('/test/{id}'), 'test');
        $method = new GetMethod();
        $controller = ControllerWithParameter::class;
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectNotToPerformAssertions();
        $route->withEndpoint($endpoint);
    }

    public function testWithVariableStrict(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $method = new GetMethod();
        $controller = ControllerWithParameter::class;
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectNotToPerformAssertions();
        $route->withEndpoint($endpoint);
    }

    public function testWithEndpointWrongVariable(): void
    {
        $route = new Route(new Path('/test/{foo}'), 'test');
        $method = new GetMethod();
        $controller = ControllerWithParameter::class;
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectException(OutOfBoundsException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithEndpointNoParams(): void
    {
        $route = new Route(new Path('/test/{foo}'), 'test');
        $method = new GetMethod();
        $controller = ControllerNoParameters::class;
        $endpoint = new Endpoint($method, bind($controller));
        $this->expectException(InvalidArgumentException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithEndpointVariableMissing(): void
    {
        $path = new Path('/test/{int:[0-9]+}');
        $controller = ControllerWithParameter::class;
        $endpoint = new Endpoint(
            new GetMethod(),
            bind($controller)
        );
        $route = new Route($path, 'test');
        $this->expectException(OutOfBoundsException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithEndpointVariableParameter(): void
    {
        $path = new Path('/test/{id:[0-9]+}');
        $route = new Route($path, 'test');
        $method = new GetMethod();
        $controller = ControllerWithParameter::class;
        $endpoint = new Endpoint($method, bind($controller));
        $route = $route->withEndpoint($endpoint);
        $this->assertTrue($route->endpoints()->has($method->name()));
    }

    public function testWithEndpointOverride(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $endpoint = new Endpoint(new GetMethod(), bind(ControllerWithParameter::class));
        $route = $route->withEndpoint($endpoint);
        $this->expectException(OverflowException::class);
        $route->withEndpoint($endpoint);
    }

    public function testWithEndpointConflictMatch(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $endpoint1 = new Endpoint(new GetMethod(), bind(ControllerWithParameter::class));
        $endpoint2 = new Endpoint(new PostMethod(), bind(ControllerRegexConflict::class));
        $route = $route->withEndpoint($endpoint1);
        $this->expectException(EndpointConflictException::class);
        $this->expectExceptionMessage('incompatible with the match /\W+/');
        $route->withEndpoint($endpoint2);
    }

    public function testWithEndpointConflictUnmatched(): void
    {
        $route = new Route(new Path('/test/{id:[0-9]+}'), 'test');
        $endpoint1 = new Endpoint(new GetMethod(), bind(ControllerWithParameter::class));
        $endpoint2 = new Endpoint(new PostMethod(), bind(ControllerNoParameters::class));
        $route = $route->withEndpoint($endpoint1);
        $this->expectException(EndpointConflictException::class);
        $this->expectExceptionMessage('incompatible with the match <none>');
        $route->withEndpoint($endpoint2);
    }

    public function testWithEndpointVariableConflict(): void
    {
        $route = new Route(new Path('/test/{id:\w+}'), 'test');
        $endpoint = new Endpoint(
            new GetMethod(),
            bind(ControllerWithParameter::class)
        );
        $this->expectException(VariableConflictException::class);
        $this->expectExceptionMessage('Variable {id} matches against');
        $route->withEndpoint($endpoint);
    }

    public function testUnboundVariables(): void
    {
        $route = new Route(new Path('/user/{id}'), 'test');
        $endpoint = new Endpoint(
            new GetMethod(),
            bind(ControllerWithParameters::class)
        );
        $this->expectException(OutOfBoundsException::class);
        $route->withEndpoint($endpoint);
    }
}
