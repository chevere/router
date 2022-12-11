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

use Chevere\Controller\HttpMiddleware;
use Chevere\Http\Exceptions\HttpMethodNotAllowedException;
use Chevere\Parameter\StringParameter;
use Chevere\Router\Exceptions\WildcardNotFoundException;
use Chevere\Router\Interfaces\EndpointInterface;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;
use Chevere\Router\Tests\_resources\MiddlewareOne;
use Chevere\Router\Tests\_resources\MiddlewareThree;
use Chevere\Router\Tests\_resources\MiddlewareTwo;
use Chevere\Router\Tests\_resources\TestControllerNoParameters;
use Chevere\Router\Tests\_resources\TestControllerWithParameters;
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
            'view' => $className,
            $method => $controller,
        ];
        $route = route(...$arguments);
        $this->assertSame($className, $route->name());
        $this->assertSame($className, $route->view());
        $this->assertTrue($route->endpoints()->has($method));
        $this->assertCount(1, $route->endpoints());
        $this->assertSame(
            $controller,
            $route->endpoints()->get($method)->httpController()
        );
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
            GET: new TestControllerNoParameters(),
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
            GET: $controller,
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
        $middleware = new HttpMiddleware(
            new MiddlewareOne(),
            new MiddlewareTwo(),
            new MiddlewareThree(),
        );
        $route = route(
            path: '/test',
            middleware: $middleware,
            GET: $controller,
        );
        $controllerWithMiddleware = $route->endpoints()->get('GET')->httpController();
        $this->assertEquals(
            $middleware,
            $controllerWithMiddleware->middleware()
        );
    }

    public function testFunctionRouteBadPath(): void
    {
        $controller = new TestControllerNoParameters();
        $this->expectException(InvalidArgumentException::class);
        route('test', 'name', GET: $controller);
    }

    public function testFunctionRouteBadHttpMethod(): void
    {
        $controller = new TestControllerNoParameters();
        $this->expectException(HttpMethodNotAllowedException::class);
        route('/test/', 'name', TEST: $controller);
    }

    public function testFunctionRoutes(): void
    {
        $name = 'test';
        $path = '/test/';
        $route = route(
            name: $name,
            path: $path,
            GET: new TestControllerNoParameters()
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
                    GET: new TestControllerNoParameters()
                )
            ),
            'api' => routes(
                route(
                    path: '/api',
                    GET: new TestControllerNoParameters()
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
