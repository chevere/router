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

use Chevere\Router\Dependencies;
use Chevere\Tests\src\ControllerWithParameter;
use Chevere\Tests\src\MiddlewareOne;
use Chevere\Tests\src\MiddlewareOneConflict;
use Chevere\Tests\src\MiddlewareTwo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;
use function Chevere\Http\middlewares;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\routes;

final class DependenciesTest extends TestCase
{
    public function testEmpty(): void
    {
        $routes = routes();
        $dependencies = new Dependencies($routes);
        $this->assertCount(0, $dependencies->parameters());
    }

    public function testEndpoint(): void
    {
        $routes = routes(
            route(
                middleware: MiddlewareOne::class,
                path: '/{id}',
                GET: bind(ControllerWithParameter::class, middleware: MiddlewareTwo::class)
            )
        );
        $dependencies = new Dependencies($routes);
        $this->assertCount(2, $dependencies->parameters());
        $this->assertSame(
            [
                'dependency', // ControllerWithParameter's dependency
                'value', // MiddlewareOne's dependency
            ],
            $dependencies->parameters()->keys()
        );
        $dependencies->parameters()(
            dependency: 'Controller dependency',
            value: 'Middleware dependency'
        );
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[dependency]: Argument #1 ($value) must be of type Stringable|string, int given'
        );
        $dependencies->parameters()(dependency: 0);
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
            'Incompatible dependency type for variable `$value` at `Chevere\Tests\src\MiddlewareOneConflict::__construct` previously defined as type `string`'
        );
        new Dependencies($routes);
    }
}
