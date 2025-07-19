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

use Chevere\Router\Dispatch;
use Chevere\Tests\src\ControllerWithParameters;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\headless;

final class DispatchTest extends TestCase
{
    public function testConstruct(): void
    {
        $controller = ControllerWithParameters::class;
        $arguments = [
            'name' => 'name-value',
            'id' => 'id-value',
        ];
        $dispatch = new Dispatch(headless($controller), $arguments);
        $this->assertSame($arguments, $dispatch->arguments());
        $this->assertSame($controller, $dispatch->bind()->controllerName()->__toString());
    }
}
