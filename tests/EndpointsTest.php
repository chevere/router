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

use Chevere\Http\Methods\GetMethod;
use function Chevere\Router\bind;
use Chevere\Router\Endpoint;
use Chevere\Router\Endpoints;
use Chevere\Tests\_resources\ArticleGetController;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class EndpointsTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new GetMethod();
        $endpoints = new Endpoints();
        $this->assertCount(0, $endpoints);
        $this->assertFalse($endpoints->has($method->name()));
        $this->expectException(OutOfBoundsException::class);
        $endpoints->get($method->name());
    }

    public function testWithPut(): void
    {
        $method = new GetMethod();
        $endpoint = new Endpoint($method, bind(ArticleGetController::class));
        $endpoints = new Endpoints();
        $endpointsWithPut = $endpoints
            ->withPut($endpoint);
        $this->assertNotSame($endpoints, $endpointsWithPut);
        $this->assertTrue($endpointsWithPut->has($method->name()));
        $this->assertSame($endpointsWithPut->get($method->name()), $endpoint);
    }
}
