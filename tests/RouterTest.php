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

use Chevere\Container\Interfaces\ContainerInterface;
use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\Methods\GetMethod;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Router;
use Chevere\Tests\src\ControllerNoParameters;
use Chevere\Tests\src\ControllerRedirect;
use Chevere\Tests\src\ControllerSetUpThrows;
use Chevere\Tests\src\ControllerThrows;
use Chevere\Tests\src\ControllerWithParameters;
use Chevere\Tests\src\MiddlewareShortCircuit;
use Chevere\Tests\src\MiddlewareWithAttribute;
use Chevere\Tests\src\RedirectIfLogged;
use FastRoute\RouteCollector;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use function Chevere\Http\middlewares;
use function Chevere\Router\bind;
use function Chevere\Router\headless;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;

final class RouterTest extends TestCase
{
    public function testConstruct(): void
    {
        $router = new Router();
        $this->assertSame([], $router->index()->toArray());
        $this->assertCount(0, $router->routes());
    }

    public function testRouter(): void
    {
        $controller = ControllerWithParameters::class;
        $bind = headless($controller);
        $route = route('/🐘/{id:\d+}/{name:\w+}');
        $route = $route->withEndpoint(
            new Endpoint(
                new GetMethod(),
                $bind
            )
        );
        $router = new Router();
        $routerWithAddedRoute = $router->withRoute($route, 'my-group');
        $this->assertNotSame($router, $routerWithAddedRoute);
        $this->assertCount(1, $routerWithAddedRoute->routes());
        $this->assertInstanceOf(
            RouteCollector::class,
            $routerWithAddedRoute->collector()
        );
        $this->assertSame(
            [],
            $routerWithAddedRoute->collector()->getData()[0]
        );
        $this->assertSame(
            [
                0 => $bind,
                1 => [
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            $routerWithAddedRoute->collector()
                ->getData()[1]['GET'][0]['routeMap'][3]
        );
        $router->views()->assert(__DIR__);
    }

    public function testConstructInvalidArgument(): void
    {
        $route = route('/test');
        $this->expectException(WithoutEndpointsException::class);
        (new Router())->withRoute($route, '');
    }

    public function testRoutedFound(): void
    {
        $request = new ServerRequest('GET', '/test');
        $bind = bind('web/test.twig', ControllerNoParameters::class);
        $router = (new Router())->withRoute(
            route(
                path: '/test',
                GET: $bind
            ),
            ''
        );
        $routed = $router->getRouted($request);
        $this->assertSame([], $routed->return());
        $this->assertEquals($bind, $routed->bind());
    }

    public static function provideRoutedNull(): array
    {
        return [
            [
                'GET',
                '/test',
                404,
                'No route found for GET `/test`',
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
        $this->expectException($exception);
        $this->expectExceptionMessage($reason);
        $this->expectExceptionCode($code);
        $router->getRouted($request);
    }

    public static function provideRoutedRequestOnShortCircuit(): array
    {
        return [
            'attribute, then reject' => [
                [MiddlewareWithAttribute::class, MiddlewareShortCircuit::class],
                429,
                'value',
            ],
            'reject, then attribute' => [
                [MiddlewareShortCircuit::class, MiddlewareWithAttribute::class],
                429,
                null,
            ],
            'attribute, then redirect' => [
                [MiddlewareWithAttribute::class, RedirectIfLogged::class],
                200,
                'value',
            ],
        ];
    }

    /**
     * @dataProvider provideRoutedRequestOnShortCircuit
     */
    public function testRoutedRequestOnShortCircuit(array $middlewares, int $code, ?string $attribute): void
    {
        $router = router(
            routes(
                route(
                    '/test',
                    middleware: middlewares(...$middlewares),
                    GET: bind('test.twig', ControllerNoParameters::class),
                )
            )
        );
        $calls = 0;
        $routed = $router->getRouted(
            new ServerRequest('GET', '/test'),
            callback: function (
                ServerRequestInterface $request,
                ContainerInterface $container
            ) use (&$calls): ContainerInterface {
                $calls++;

                return $container;
            }
        );
        $this->assertSame($code, $routed->response()->getStatusCode());
        $this->assertSame(
            $attribute,
            $routed->request()->getAttribute(MiddlewareWithAttribute::ATTRIBUTE)
        );
        $this->assertSame([], $routed->request()->getAttribute('arguments'));
        $this->assertSame(0, $calls);
    }

    public function testRoutedRequestOnPassThrough(): void
    {
        $router = router(
            routes(
                route(
                    '/test',
                    middleware: MiddlewareWithAttribute::class,
                    GET: bind('test.twig', ControllerNoParameters::class),
                )
            )
        );
        $requests = [];
        $routed = $router->getRouted(
            new ServerRequest('GET', '/test'),
            callback: function (
                ServerRequestInterface $request,
                ContainerInterface $container
            ) use (&$requests): ContainerInterface {
                $requests[] = $request;

                return $container;
            }
        );
        $this->assertSame([], $routed->return());
        $this->assertSame(
            'value',
            $routed->request()->getAttribute(MiddlewareWithAttribute::ATTRIBUTE)
        );
        $this->assertCount(1, $requests);
        $this->assertSame(
            'value',
            $requests[0]->getAttribute(MiddlewareWithAttribute::ATTRIBUTE)
        );
    }

    public static function provideRoutedRequestOnController(): array
    {
        return [
            'return' => [ControllerNoParameters::class, 200, false],
            'invoke throws' => [ControllerThrows::class, 500, true],
            'setUp throws' => [ControllerSetUpThrows::class, 400, true],
        ];
    }

    /**
     * @dataProvider provideRoutedRequestOnController
     */
    public function testRoutedRequestOnController(string $controller, int $code, bool $hasThrowable): void
    {
        $router = router(
            routes(
                route(
                    '/test',
                    middleware: MiddlewareWithAttribute::class,
                    GET: bind('test.twig', $controller),
                )
            )
        );
        $routed = $router->getRouted(new ServerRequest('GET', '/test'));
        $this->assertSame($code, $routed->response()->getStatusCode());
        $this->assertSame($hasThrowable, $routed->throwable() !== null);
        $this->assertSame(
            'value',
            $routed->request()->getAttribute(MiddlewareWithAttribute::ATTRIBUTE)
        );
    }

    public function testRoutedRequestOnControllerLocation(): void
    {
        $router = router(
            routes(
                route(
                    '/test',
                    middleware: MiddlewareWithAttribute::class,
                    GET: bind('test.twig', ControllerRedirect::class),
                )
            )
        );
        $routed = $router->getRouted(new ServerRequest('GET', '/test'));
        $this->assertSame('/account', $routed->response()->getHeaderLine('Location'));
        $this->assertSame(
            'value',
            $routed->request()->getAttribute(MiddlewareWithAttribute::ATTRIBUTE)
        );
    }
}
