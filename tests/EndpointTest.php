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

use Chevere\Http\Methods\GetMethod;
use Chevere\Router\Endpoint;
use Chevere\Router\Tests\_resources\RouteEndpointTestController;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class EndpointTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new GetMethod();
        $controller = new RouteEndpointTestController();
        $endpoint = new Endpoint($method, $controller);
        $this->assertSame($method, $endpoint->method());
        $this->assertSame($controller, $endpoint->controller());
        $this->assertSame($method->description(), $endpoint->description());
        /** @var string $name */
        foreach (array_keys($endpoint->parameters()) as $name) {
            $this->assertTrue($controller->parameters()->has($name));
        }
    }

    public function testWithDescription(): void
    {
        $description = 'Some description';
        $endpoint = (new Endpoint(new GetMethod(), new RouteEndpointTestController()))
            ->withDescription($description);
        $this->assertSame($description, $endpoint->description());
    }

    public function testWithoutWrongParameter(): void
    {
        $controller = new RouteEndpointTestController();
        $this->expectException(OutOfBoundsException::class);
        (new Endpoint(new GetMethod(), $controller))
            ->withoutParameter('0x0');
    }

    public function testWithoutParameter(): void
    {
        $controller = new RouteEndpointTestController();
        $iterator = $controller->parameters()->getIterator();
        $iterator->rewind();
        $key = $iterator->key() ?? 'name';
        $endpoint = (new Endpoint(new GetMethod(), $controller))
            ->withoutParameter($key);
        $this->assertArrayNotHasKey($key, $endpoint->parameters());
    }
}
