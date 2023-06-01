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

use Chevere\Http\HttpControllerName;
use function Chevere\Http\middlewares;
use Chevere\Router\Bind;
use Chevere\Tests\_resources\ControllerWithParameters;
use PHPUnit\Framework\TestCase;

final class BindTest extends TestCase
{
    public function testConstruct(): void
    {
        $controller = ControllerWithParameters::class;
        $view = 'test';
        $controllerName = new HttpControllerName($controller);
        $middlewares = middlewares();
        $bind = new Bind($controllerName, $middlewares, $view);
        $this->assertSame($controllerName, $bind->controllerName());
        $this->assertSame($view, $bind->view());
        $this->assertSame($middlewares, $bind->middlewares());
    }

    public function testWithMiddlewares(): void
    {
        $controller = ControllerWithParameters::class;
        $view = 'test';
        $controllerName = new HttpControllerName($controller);
        $middlewares = middlewares();
        $bind = new Bind($controllerName, $middlewares, $view);
        $middlewaresAlt = middlewares();
        $bindWith = $bind->withMiddlewares($middlewaresAlt);
        $this->assertNotSame($bind, $bindWith);
        $this->assertSame($middlewaresAlt, $bindWith->middlewares());
    }
}
