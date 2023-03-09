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

use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\Middlewares;
use Chevere\Parameter\StringParameter;
use function Chevere\Router\bind;
use Chevere\Router\Exceptions\WildcardNotFoundException;
use Chevere\Router\Interfaces\EndpointInterface;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;
use Chevere\Tests\_resources\MiddlewareOne;
use Chevere\Tests\_resources\MiddlewareThree;
use Chevere\Tests\_resources\MiddlewareTwo;
use Chevere\Tests\_resources\TestControllerNoParameters;
use Chevere\Tests\_resources\TestControllerWithParameters;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FunctionsTest extends TestCase
{
    public function functionRouteProvider(): array
    {
        $return = [];
        foreach (EndpointInterface::KNOWN_METHODS as $method => $className) {
            $return[] = [$method, $className];
        }

        return $return;
    }

    /**
     * @dataProvider functionRouteProvider
     */
    public function testFunctionRoute(string $method, string $className): void
    {
        $controller = new TestControllerNoParameters();
        $arguments = [
            'path' => '/test/',
            'name' => $className,
            $method => bind($controller),
        ];
        $route = route(...$arguments);
        $this->assertSame($className, $route->name());
        $this->assertTrue($route->endpoints()->has($method));
        $this->assertCount(1, $route->endpoints());
        $this->assertSame(
            $controller,
            $route->endpoints()->get($method)->bind()->controller()
        );
    }

    /**
     * @dataProvider functionRouteViewDataProvider
     */
    public function testFunctionRouteViewNamespace(array $arguments, string $expectedView): void
    {
        $arguments = array_merge([
            'path' => '/test/',
        ], $arguments);
        $route = route(...$arguments);
        $this->assertSame(
            $expectedView,
            $route->endpoints()->get('GET')->bind()->view()
        );
    }

    public function functionRouteViewDataProvider(): array
    {
        $controller = new TestControllerNoParameters();

        return [
            [
                [
                    'GET' => $controller,
                ],
                '',
            ],
            [
                [
                    'GET' => bind($controller),
                ],
                'GET',
            ],
            [
                [
                    'GET' => bind($controller, 'test'),
                ],
                'test/GET',
            ],
        ];
    }

    public function testFunctionRouteWildcardNotFound(): void
    {
        $this->expectException(WildcardNotFoundException::class);
        $this->expectExceptionMessage(
            'Wildcard {wildcard} does not exists in controller '
            . TestControllerNoParameters::class
        );
        route(
            path: '/test/{wildcard}',
            GET: bind(new TestControllerNoParameters()),
        );
    }

    public function testFunctionRouteWildcard(): void
    {
        $controller = new TestControllerWithParameters();
        /** @var StringParameter $id */
        $id = $controller->parameters()->get('id');
        /** @var StringParameter $name */
        $name = $controller->parameters()->get('name');
        $route = route(
            path: '/test/{id}/{name}',
            GET: bind($controller),
        );
        $this->assertSame(
            strtr('/test/{id:%id%}/{name:%name%}', [
                '%id%' => $id->regex()->noDelimiters(),
                '%name%' => $name->regex()->noDelimiters(),
            ]),
            strval($route->path())
        );
    }

    public function testFunctionRouteMiddleware(): void
    {
        $controller = new TestControllerNoParameters();
        $middleware = new Middlewares(
            new MiddlewareOne(),
            new MiddlewareTwo(),
            new MiddlewareThree(),
        );
        $route = route(
            path: '/test',
            middleware: $middleware,
            GET: bind($controller),
        );
        $controllerWithMiddleware = $route->endpoints()->get('GET')->bind()->controller();
        $this->assertEquals(
            $middleware,
            $controllerWithMiddleware->middlewares()
        );
    }

    public function testFunctionRouteBadPath(): void
    {
        $controller = new TestControllerNoParameters();
        $this->expectException(InvalidArgumentException::class);
        route('test', 'name', GET: bind($controller));
    }

    public function testFunctionRouteBadHttpMethod(): void
    {
        $controller = new TestControllerNoParameters();
        $this->expectException(MethodNotAllowedException::class);
        route('/test/', 'name', TEST: bind($controller));
    }

    public function testFunctionRoutes(): void
    {
        $name = 'test';
        $path = '/test/';
        $route = route(
            name: $name,
            path: $path,
            GET: bind(new TestControllerNoParameters())
        );
        $routes = routes(myRoute: $route);
        $this->assertTrue($routes->has($path));
        $this->assertSame($route, $routes->get($path));
    }

    public function testRouterFunction(): void
    {
        $routes = [
            'web' => routes(
                route(
                    path: '/',
                    GET: bind(new TestControllerNoParameters())
                )
            ),
            'api' => routes(
                route(
                    path: '/api',
                    GET: bind(new TestControllerNoParameters())
                )
            ),
        ];
        $router = router(...$routes);
        $this->assertCount(2, $router->routes());
        foreach (array_keys($routes) as $key) {
            $this->assertTrue($router->index()->hasGroup($key));
        }
    }
}
