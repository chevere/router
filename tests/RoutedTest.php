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
use Exception;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\bind;

final class RoutedTest extends TestCase
{
    public function testConstruct(): void
    {
        $request = new ServerRequest('GET', '/test');
        $response = new Response();
        $bind = bind('test', ControllerNoParameters::class);
        $routed = new Routed($request, $response, $bind);
        $this->assertSame($routed->bind(), $bind);
        $this->assertSame($routed->response(), $response);
        $this->assertNull($routed->return());
        $this->assertNull($routed->throwable());
    }

    public function testConstructWithReturn(): void
    {
        $request = new ServerRequest('GET', '/test');
        $response = new Response();
        $bind = bind('test', ControllerNoParameters::class);
        $raw = [];
        $routed = new Routed($request, $response, $bind, return: $raw);
        $this->assertSame($raw, $routed->return());
    }

    public function testConstructWithThrowable(): void
    {
        $request = new ServerRequest('GET', '/test');
        $response = new Response();
        $bind = bind('test', ControllerNoParameters::class);
        $throwable = new Exception('test');
        $routed = new Routed($request, $response, $bind, throwable: $throwable);
        $this->assertSame($routed->throwable(), $throwable);
    }
}
