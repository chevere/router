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
use Chevere\Http\Methods\GetMethod;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Router;
use Chevere\Tests\src\ControllerNoParameters;
use Chevere\Tests\src\ControllerWithParameters;
use FastRoute\RouteCollector;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
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
}
