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
use Exception;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\bind;

final class RoutedTest extends TestCase
{
    public function testConstruct(): void
    {
        $response = new Response();
        $bind = bind(ControllerNoParameters::class, 'test');
        $routed = (new Routed($response, $bind));
        $this->assertSame($routed->bind(), $bind);
        $this->assertSame($routed->response(), $response);
        $this->assertFalse($routed->hasThrowable());
        $this->assertFalse($routed->hasRaw());
    }

    public function testWithRaw(): void
    {
        $raw = [];
        $response = new Response();
        $bind = bind(ControllerNoParameters::class, 'test');
        $routed = (new Routed($response, $bind));
        $with = $routed->withRaw($raw);
        $this->assertNotSame($routed, $with);
        $this->assertSame($with->raw(), $raw);
    }

    public function testWithNoRaw(): void
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

    public function testWithThrowable(): void
    {
        $throwable = new Exception('test');
        $response = new Response();
        $bind = bind(ControllerNoParameters::class, 'test');
        $routed = (new Routed($response, $bind));
        $with = $routed->withThrowable($throwable);
        $this->assertNotSame($routed, $with);
        $this->assertSame($with->throwable(), $throwable);
    }

    public function testWithNoThrowable(): void
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
