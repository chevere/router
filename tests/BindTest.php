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

use Chevere\Router\Bind;
use Chevere\Tests\_resources\ControllerWithParameters;
use PHPUnit\Framework\TestCase;

final class BindTest extends TestCase
{
    public function testConstruct(): void
    {
        $controller = new ControllerWithParameters();
        $view = 'test';
        $bind = new Bind($controller, $view);
        $this->assertSame($controller, $bind->controller());
        $this->assertSame($view, $bind->view());
    }
}
