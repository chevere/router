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
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Exceptions\VariableNotFoundException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Tests\src\ControllerNoParameters;
use Chevere\Tests\src\ControllerWithParameters;
use Chevere\Tests\src\WrongController;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use function Chevere\Action\getParameters;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;

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

    public function testFunctionVariableNotFound(): void
    {
        $this->expectException(VariableNotFoundException::class);
        $this->expectExceptionMessage(
            'Variable {variable} does not exists in controller '
            . ControllerNoParameters::class
        );
        route(
            path: '/test/{variable}',
            GET: bind(ControllerNoParameters::class),
        );
    }

    public function testFunctionVariable(): void
    {
        $controller = ControllerWithParameters::class;
        $parameters = getParameters($controller);
        $id = $parameters->getString('id');
        $name = $parameters->getString('name');
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
        $this->expectException(VariableInvalidException::class);
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
