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
use Chevere\Parameter\StringParameter;
use function Chevere\Router\bind;
use Chevere\Router\Exceptions\WildcardInvalidException;
use Chevere\Router\Exceptions\WildcardNotFoundException;
use Chevere\Router\Interfaces\EndpointInterface;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;
use Chevere\Tests\_resources\ControllerNoParameters;
use Chevere\Tests\_resources\ControllerWithParameters;
use Chevere\Tests\_resources\WrongController;
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
        $controller = ControllerNoParameters::class;
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
            $route->endpoints()->get($method)->bind()->controllerName()->__toString()
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
        $controller = ControllerNoParameters::class;

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
                    'GET' => bind(controller: $controller, view: 'test'),
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
            . ControllerNoParameters::class
        );
        route(
            path: '/test/{wildcard}',
            GET: bind(ControllerNoParameters::class),
        );
    }

    public function testFunctionRouteWildcard(): void
    {
        $controller = ControllerWithParameters::class;
        /** @var StringParameter $id */
        $id = $controller::getParameters()->get('id');
        /** @var StringParameter $name */
        $name = $controller::getParameters()->get('name');
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

    public function testFunctionRouteInvalidPath(): void
    {
        $controller = ControllerNoParameters::class;
        $this->expectException(InvalidArgumentException::class);
        route('test', 'name', GET: bind($controller));
    }

    public function testFunctionRouteInvalidMethod(): void
    {
        $controller = ControllerNoParameters::class;
        $this->expectException(MethodNotAllowedException::class);
        route('/test/', 'name', TEST: bind($controller));
    }

    public function testFunctionRouteInvalidController(): void
    {
        $this->expectException(WildcardInvalidException::class);
        route(path: '/{id}', GET: bind(WrongController::class));
    }

    public function testFunctionRoutes(): void
    {
        $name = 'test';
        $path = '/test/';
        $route = route(
            name: $name,
            path: $path,
            GET: bind(ControllerNoParameters::class)
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
                    GET: bind(ControllerNoParameters::class)
                )
            ),
            'api' => routes(
                route(
                    path: '/api',
                    GET: bind(ControllerNoParameters::class)
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
