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

use Chevere\Http\ControllerName;
use Chevere\Router\Bind;
use Chevere\Tests\src\ControllerWithParameters;
use PHPUnit\Framework\TestCase;
use function Chevere\Http\middlewares;

final class BindTest extends TestCase
{
    public function testConstruct(): void
    {
        $controller = ControllerWithParameters::class;
        $view = 'test';
        $controllerName = new ControllerName($controller);
        $middlewares = middlewares();
        $bind = new Bind($controllerName, $middlewares);
        $bind = $bind->withView($view);
        $this->assertSame($controllerName, $bind->controllerName());
        $this->assertSame($view, $bind->view());
        $this->assertSame($middlewares, $bind->middlewares());
    }

    public function testWithMiddlewares(): void
    {
        $controller = ControllerWithParameters::class;
        $controllerName = new ControllerName($controller);
        $middlewares = middlewares();
        $bind = new Bind($controllerName, $middlewares);
        $middlewaresAlt = middlewares();
        $bindWith = $bind->withMiddlewares($middlewaresAlt);
        $this->assertNotSame($bind, $bindWith);
        $this->assertSame($middlewaresAlt, $bindWith->middlewares());
    }
}
