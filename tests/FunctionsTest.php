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
use Chevere\Router\Tests\_resources\TestController;
use Chevere\Router\Tests\_resources\TestDummyController;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FunctionsTest extends TestCase
{
    public function testFunctionRoute(): void
    {
        $controller = new TestDummyController();
        foreach (EndpointInterface::KNOWN_METHODS as $httpMethod => $className) {
            $arguments = [
                'path' => '/test/',
                'name' => $className,
                'view' => $className,
                ...[
                    $httpMethod => $controller,
                ],
            ];
            $route = route(...$arguments);
            $this->assertSame($className, $route->name());
            $this->assertSame($className, $route->view());
            $this->assertTrue($route->endpoints()->has($httpMethod));
            $this->assertCount(1, $route->endpoints());
            $this->assertSame(
                $controller,
                $route->endpoints()->get($httpMethod)->httpController()
            );
        }
    }

    public function testFunctionRouteWildcardNotFound(): void
    {
        $this->expectException(WildcardNotFoundException::class);
        $this->expectExceptionMessage(
            'Wildcard {wildcard} does not exists in controller '
            . TestDummyController::class
        );
        route(
            path: '/test/{wildcard}',
            GET: new TestDummyController(),
        );
    }

    public function testFunctionRouteWildcard(): void
    {
        $controller = new TestController();
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
        $controller = new TestDummyController();
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
        $controller = new TestDummyController();
        $this->expectException(InvalidArgumentException::class);
        route('test', 'name', GET: $controller);
    }

    public function testFunctionRouteBadHttpMethod(): void
    {
        $controller = new TestDummyController();
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
            GET: new TestDummyController()
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
                    GET: new TestDummyController()
                )
            ),
            'api' => routes(
                route(
                    path: '/api',
                    GET: new TestDummyController()
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
