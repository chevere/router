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
use Chevere\Router\Routed;
use Chevere\Tests\_resources\ControllerWithParameters;
use PHPUnit\Framework\TestCase;

final class RoutedTest extends TestCase
{
    public function testConstruct(): void
    {
        $controller = ControllerWithParameters::class;
        $arguments = [
            'name' => 'name-value',
            'id' => 'id-value',
        ];
        $routed = new Routed(bind($controller), $arguments);
        $this->assertSame($arguments, $routed->arguments());
        $this->assertSame($controller, $routed->bind()->controllerName());
    }
}
