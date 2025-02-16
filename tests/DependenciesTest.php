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

use Chevere\DataStructure\Vector;
use Chevere\Router\Dependencies;
use Chevere\Router\Path;
use Chevere\Tests\src\ControllerWithDependencies;
use Chevere\Tests\src\ControllerWithParameter;
use Chevere\Tests\src\MiddlewareOne;
use Chevere\Tests\src\MiddlewareOneConflict;
use Chevere\Tests\src\MiddlewareTwo;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use TypeError;
use function Chevere\Http\middlewares;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;

final class DependenciesTest extends TestCase
{
    public function testEmpty(): void
    {
        $routes = routes();
        $dependencies = new Dependencies($routes);
        $this->assertCount(0, $dependencies->parameters());
        $this->assertFalse($dependencies->has('className'));
        $this->assertSame(
            [],
            $dependencies->extract(
                'className',
                [
                    'key' => 'value',
                ]
            )
        );
        $this->expectException(OutOfBoundsException::class);
        $dependencies->get('className');
    }

    public function testWithRoute(): void
    {
        $routes = routes();
        $dependencies = new Dependencies($routes);
        $with = $dependencies->withRoute();
        $this->assertNotSame($dependencies, $with);
        $this->assertEquals($dependencies, $with);
    }

    public function testEmptyRequirer(): void
    {
        $routes = routes();
        $dependencies = new Dependencies($routes);
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Dependency `` not defined');
        $dependencies->requirer('');
    }

    public function testEndpoint(): void
    {
        $routes = routes(
            route(
                middleware: MiddlewareOne::class,
                path: '/{id}',
                GET: bind(ControllerWithDependencies::class, middleware: MiddlewareTwo::class)
            )
        );
        $router = router($routes);
        $dependencies = (new Dependencies())->withRoute(...$routes);
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
        $container = [
            'dependency' => new Path('/test'),
            'int' => 123,
            'value' => 'Middleware dependency',
            'extra' => 'Extra value',
        ];
        $dependencies->assert(int: 1);
        $dependencies->assert(...$container);
        $this->assertSame(
            [
                'dependency' => $container['dependency'],
                'int' => $container['int'],
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

    public function testAssert(): void
    {
        $routes = routes(
            route(
                middleware: MiddlewareOne::class,
                path: '/{id}',
                GET: bind(ControllerWithDependencies::class, middleware: MiddlewareTwo::class)
            )
        );
        $reflector = new \ReflectionMethod(ControllerWithDependencies::class, '__construct');
        $fileLine = $reflector->getFileName() . ':' . $reflector->getStartLine();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            <<<PLAIN
            - [1]: Missing argument `int` as previously defined by `Chevere\Tests\src\ControllerWithDependencies` in {$fileLine}
            PLAIN
        );
        $this->expectExceptionMessage(
            <<<PLAIN
            - [2]: Argument `dependency` provided as `Chevere\DataStructure\Vector` is not compatible with `Chevere\Router\Path` as previously defined by `Chevere\Tests\src\ControllerWithDependencies` in {$fileLine}
            PLAIN
        );
        $dependencies = new Dependencies($routes);
        $dependencies->assert(dependency: new Vector());
    }

    public function testIncompatibleDependencies(): void
    {
        $routes = routes(
            route(
                middleware: middlewares(MiddlewareOne::class, MiddlewareOneConflict::class),
                path: '/{id}',
                GET: bind(ControllerWithParameter::class, middleware: MiddlewareTwo::class)
            )
        );
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            'Variable `$value` defined as `int` is not compatible with `string` as previously defined by `Chevere\Tests\src\MiddlewareOne` in '
        );
        new Dependencies($routes);
    }
}
