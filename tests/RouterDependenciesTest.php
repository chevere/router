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

use Chevere\Container\Container;
use Chevere\Container\Dependencies;
use Chevere\DataStructure\Vector;
use Chevere\Router\Path;
use Chevere\Tests\src\ControllerWithDependencies;
use Chevere\Tests\src\MiddlewareOne;
use Chevere\Tests\src\MiddlewareTwo;
use InvalidArgumentException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\headless;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;

final class RouterDependenciesTest extends TestCase
{
    public function testEmpty(): void
    {
        $router = router();
        $this->assertCount(0, $router->dependencies()->parameters());
        $this->assertFalse($router->dependencies()->has('className'));
        $this->assertSame(
            [],
            $router->dependencies()->extract(
                'className',
                new Container(
                    key: 'value',
                )
            )
        );
        $this->expectException(OutOfBoundsException::class);
        $router->dependencies()->get('className');
    }

    public function testWithRoute(): void
    {
        $dependencies = router()->dependencies();
        $with = new Dependencies();
        $this->assertNotSame($dependencies, $with);
        $this->assertEquals($dependencies, $with);
    }

    public function testEndpoint(): void
    {
        $routes = routes(
            route(
                middleware: MiddlewareOne::class,
                path: '/{id}',
                GET: headless(ControllerWithDependencies::class, middleware: MiddlewareTwo::class)
            )
        );
        $router = router($routes);
        $dependencies = $router->dependencies();
        $this->assertEquals($dependencies, $router->dependencies());
        $this->assertCount(3, $dependencies->parameters());
        $this->assertSame(
            [
                'int', // ControllerWithDependencies's int
                'dependency', // ControllerWithDependencies's dependency
                'value', // MiddlewareOne's dependency,
            ],
            $dependencies->parameters()->keys()
        );
        $container = new Container(
            int: 123,
            dependency: new Path('/test'),
            value: 'Middleware dependency',
            extra: 'Extra value',
        );
        $dependencies->assert($container);
        $this->assertSame(
            [
                'int' => $container->get('int'),
                'dependency' => $container->get('dependency'),
            ],
            $dependencies->extract(ControllerWithDependencies::class, $container)
        );

        $dependencies->parameters()(...$container);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[dependency]: Argument value provided is not of type `Chevere\Router\Path`'
        );
        $dependencies->parameters()(int: 0, dependency: new Vector());
    }
}
