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

use Chevere\Http\Controllers\NullController;
use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\MiddlewareName;
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Exceptions\VariableNotFoundException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Routes;
use Chevere\Tests\src\ControllerNoParameters;
use Chevere\Tests\src\ControllerWithParameters;
use Chevere\Tests\src\MiddlewareOne;
use Chevere\Tests\src\MiddlewareTwo;
use Chevere\Tests\src\WrongController;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function Chevere\Action\getParameters;
use function Chevere\Router\bind;
use function Chevere\Router\headless;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;

final class FunctionsTest extends TestCase
{
    public static function dataProviderRoute(): array
    {
        $return = [];
        foreach (EndpointInterface::KNOWN_METHODS as $method => $className) {
            $return[] = [$method, $className];
        }

        return $return;
    }

    #[DataProvider('dataProviderRoute')]
    public function testRoute(string $method, string $className): void
    {
        $controller = ControllerNoParameters::class;
        $arguments = [
            'path' => '/test/',
            'name' => $className,
            $method => $controller,
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

    #[DataProvider('functionRouteViewDataProvider')]
    public function testRouteViewNamespace(array $arguments, string $expectedView): void
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

    public static function functionRouteViewDataProvider(): array
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
                    'GET' => headless($controller, MiddlewareOne::class),
                ],
                '',
            ],
            [
                [
                    'GET' => bind('test.twig', $controller),
                ],
                'test.twig',
            ],
        ];
    }

    public function testRouteVariableNotFound(): void
    {
        $this->expectException(VariableNotFoundException::class);
        $this->expectExceptionMessage(
            'Variable `{variable}` does not exists in controller `'
            . ControllerNoParameters::class
            . '`'
        );
        route(
            path: '/test/{variable}',
            GET: ControllerNoParameters::class,
        );
    }

    public function testVariable(): void
    {
        $controller = ControllerWithParameters::class;
        $parameters = getParameters($controller);
        $id = $parameters->required('id')->string();
        $name = $parameters->required('name')->string();
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

    public function testRouteInvalidPath(): void
    {
        $controller = ControllerNoParameters::class;
        $this->expectException(InvalidArgumentException::class);
        route('test', 'name', GET: $controller);
    }

    public function testRouteInvalidMethod(): void
    {
        $controller = ControllerNoParameters::class;
        $this->expectException(MethodNotAllowedException::class);
        route('/test/', 'name', TEST: $controller);
    }

    public function testRouteInvalidController(): void
    {
        $this->expectException(VariableInvalidException::class);
        route(path: '/{id}', GET: WrongController::class);
    }

    public function testRoutes(): void
    {
        $routeA = route(
            name: 'a',
            path: '/a/',
            GET: ControllerNoParameters::class
        );
        $routeB = route(
            name: 'b',
            path: '/b/',
            GET: ControllerNoParameters::class
        );
        $routes = routes($routeA, $routeB);
        $routesAlt = routes($routes);
        $this->assertEquals($routes, $routesAlt);
        $this->assertEquals(
            (new Routes())->withRoute($routeA, $routeB),
            $routes
        );
    }

    public function testRouter(): void
    {
        $routes = [
            'web' => routes(
                route(
                    path: '/',
                    GET: ControllerNoParameters::class
                )
            ),
            'api' => routes(
                route(
                    path: '/api',
                    GET: ControllerNoParameters::class
                )
            ),
        ];
        $router = router(...$routes);
        $this->assertCount(2, $router->routes());
        foreach (array_keys($routes) as $key) {
            $this->assertTrue($router->index()->hasGroup($key));
        }
    }

    public function testBindEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        bind('');
    }

    public function testBind(): void
    {
        $bind = bind(
            'view',
            ControllerNoParameters::class,
            MiddlewareOne::class,
            new MiddlewareName(MiddlewareTwo::class)
        );
        $this->assertSame(
            ControllerNoParameters::class,
            $bind->controllerName()->__toString(),
        );
        $this->assertSame('view', $bind->view());
        $this->assertCount(
            2,
            $bind->middlewares()
        );
        $this->assertTrue(
            $bind->middlewares()->has(MiddlewareOne::class, MiddlewareTwo::class)
        );
    }

    public function testHeadless(): void
    {
        $bind = headless(
            ControllerNoParameters::class,
            MiddlewareOne::class,
            new MiddlewareName(MiddlewareTwo::class)
        );
        $this->assertSame(
            ControllerNoParameters::class,
            $bind->controllerName()->__toString(),
        );
        $this->assertSame('', $bind->view());
        $this->assertCount(
            2,
            $bind->middlewares()
        );
        $this->assertTrue(
            $bind->middlewares()->has(MiddlewareOne::class, MiddlewareTwo::class)
        );
    }

    public static function functionRouteBindingsDataProvider(): array
    {
        return [
            [
                [
                    'path' => '/',
                    'GET' => 'home.twig',
                ],
            ],
            [
                [
                    'path' => '/',
                    'GET' => bind('home.twig'),
                ],
            ],
            [
                [
                    'path' => '/',
                    'GET' => bind('home.twig', NullController::class),
                ],
            ],
            [
                [
                    'path' => '/',
                    'view' => 'home.twig',
                    'GET' => NullController::class,
                ],
            ],
        ];
    }

    /**
     * @dataProvider functionRouteBindingsDataProvider
     */
    public function testRouteBindings(array $arguments): void
    {
        $route = route(
            '/',
            GET: 'home.twig'
        );
        $this->assertEquals(
            $route->endpoints(),
            route(...$arguments)->endpoints(),
        );
    }
}
