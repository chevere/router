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

namespace Chevere\Router\Tests;

use Chevere\Router\Routed;
use Chevere\Router\Tests\_resources\TestController;
use PHPUnit\Framework\TestCase;

final class RoutedTest extends TestCase
{
    public function testConstruct(): void
    {
        $controller = new TestController();
        $arguments = [
            'name' => 'name-value',
            'id' => 'id-value',
        ];
        $routed = new Routed($controller, $arguments);
        $this->assertSame($arguments, $routed->arguments());
        $this->assertSame($controller, $routed->controller());
    }
}
