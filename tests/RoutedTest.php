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

use Chevere\Router\Routed;
use Chevere\Tests\src\ControllerNoParameters;
use Error;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\bind;

final class RoutedTest extends TestCase
{
    public function testRouted(): void
    {
        $response = new Response();
        $bind = bind(ControllerNoParameters::class, 'test');
        $raw = [];
        $routed = (new Routed($response, $bind))->withRaw($raw);
        $this->assertSame($routed->bind(), $bind);
        $this->assertSame($routed->response(), $response);
        $this->assertSame($routed->raw(), $raw);
        $this->assertSame($routed->type()->primitive(), 'array');
    }

    public function testNoRaw(): void
    {
        $response = new Response();
        $bind = bind(ControllerNoParameters::class, 'test');
        $routed = (new Routed($response, $bind));
        $this->expectException(Error::class);
        $this->expectExceptionMessage(
            <<<PLAIN
            \$raw must not be accessed before initialization
            PLAIN
        );
        $routed->raw();
    }

    public function testNoThrowable(): void
    {
        $response = new Response();
        $bind = bind(ControllerNoParameters::class, 'test');
        $routed = (new Routed($response, $bind));
        $this->expectException(Error::class);
        $this->expectExceptionMessage(
            <<<PLAIN
            \$throwable must not be accessed before initialization
            PLAIN
        );
        $routed->throwable();
    }
}
