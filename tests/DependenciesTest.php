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

use function Chevere\Router\bind;
use Chevere\Router\Dependencies;
use function Chevere\Router\route;
use function Chevere\Router\routes;
use Chevere\Tests\_resources\ControllerWithParameter;
use Chevere\Tests\_resources\MiddlewareOne;
use Chevere\Tests\_resources\MiddlewareTwo;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class DependenciesTest extends TestCase
{
    public function testEmpty(): void
    {
        $routes = routes();
        $dependencies = new Dependencies($routes);
        $this->assertSame([], $dependencies->toArray());
        $this->assertCount(0, $dependencies);
        $this->expectException(OutOfBoundsException::class);
        $dependencies->get('404');
    }

    public function testEndpoint(): void
    {
        $routes = routes(
            route(
                middleware: MiddlewareOne::class,
                path: '/{id}',
                GET: bind(ControllerWithParameter::class, MiddlewareTwo::class)
            )
        );
        $dependencies = new Dependencies($routes);
        $this->assertCount(2, $dependencies);
        $this->assertSame(
            [
                ControllerWithParameter::class,
                MiddlewareOne::class,
            ],
            $dependencies->keys()
        );
        $dependency = $dependencies->toArray()[ControllerWithParameter::class]['dependency'];
        $this->assertFalse($dependency['required']);
    }
}
