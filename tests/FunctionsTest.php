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
use Chevere\Parameter\Type;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Exceptions\VariableNotFoundException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Tests\src\ControllerNoParameters;
use Chevere\Tests\src\ControllerWithParameters;
use Chevere\Tests\src\MiddlewareOne;
use Chevere\Tests\src\WrongController;
use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use function Chevere\Action\getParameters;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\routed;
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
                    'GET' => bind($controller, middleware: MiddlewareOne::class),
                ],
                '',
            ],
            [
                [
                    'GET' => bind($controller, 'test.twig'),
                ],
                'test.twig',
            ],
        ];
    }

    public function testFunctionVariableNotFound(): void
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

    public function testFunctionVariable(): void
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

    public function testFunctionRouteInvalidPath(): void
    {
        $controller = ControllerNoParameters::class;
        $this->expectException(InvalidArgumentException::class);
        route('test', 'name', GET: $controller);
    }

    public function testFunctionRouteInvalidMethod(): void
    {
        $controller = ControllerNoParameters::class;
        $this->expectException(MethodNotAllowedException::class);
        route('/test/', 'name', TEST: $controller);
    }

    public function testFunctionRouteInvalidController(): void
    {
        $this->expectException(VariableInvalidException::class);
        route(path: '/{id}', GET: WrongController::class);
    }

    public function testFunctionRoutes(): void
    {
        $name = 'test';
        $path = '/test/';
        $route = route(
            name: $name,
            path: $path,
            GET: ControllerNoParameters::class
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

    public function testRoutedFound(): void
    {
        $request = new ServerRequest('GET', '/test');
        $bind = bind(ControllerNoParameters::class, 'web/test.twig');
        $router = router(
            routes(
                route(
                    path: '/test',
                    GET: $bind
                )
            )
        );
        $routed = routed($request, $router);
        $this->assertSame([], $routed->raw());
        $this->assertEquals($bind, $routed->bind());
        $this->assertEquals(new Type('array'), $routed->type());
    }

    public static function provideRoutedNull(): array
    {
        return [
            [
                'GET',
                '/test',
                404,
                'No route found for `/test`',
                NotFoundException::class,
            ],
            [
                'POST',
                '/foo',
                405,
                'Method `POST` is not in the list of allowed methods: `PATCH`',
                MethodNotAllowedException::class,
            ],
        ];
    }

    /**
     * @dataProvider provideRoutedNull
     */
    public function testRoutedNull(string $method, string $path, int $code, string $reason, string $exception): void
    {
        $request = new ServerRequest($method, $path);
        $router = router(
            routes(
                route('/foo', PATCH: ControllerNoParameters::class)
            )
        );
        $routed = routed($request, $router);
        $this->assertSame($code, $routed->response()->getStatusCode());
        $this->assertInstanceOf($exception, $routed->throwable());
        $this->assertSame($reason, $routed->throwable()->getMessage());
        $this->assertSame(
            NullController::class,
            $routed->bind()->controllerName()->__toString()
        );
    }
}
