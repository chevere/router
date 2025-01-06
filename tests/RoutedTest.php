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
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RoutedTest extends TestCase
{
    public function testRouted(): void
    {
        $response = new Response();
        $view = 'test';
        $raw = [];
        $routed = new Routed($response, $view, $raw);
        $this->assertSame($routed->response(), $response);
        $this->assertSame($routed->raw(), $raw);
        $this->assertSame($routed->type()->primitive(), 'array');
    }
}
