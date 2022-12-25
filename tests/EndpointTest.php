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
use function Chevere\Router\bind;
use Chevere\Router\Endpoint;
use Chevere\Router\Tests\_resources\EndpointTestController;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class EndpointTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new GetMethod();
        $controller = new EndpointTestController();
        $bind = bind($controller);
        $endpoint = new Endpoint($method, $bind);
        $this->assertSame($method, $endpoint->method());
        $this->assertSame($bind, $endpoint->bind());
        $this->assertSame($method->description(), $endpoint->description());
        /** @var string $name */
        foreach (array_keys($endpoint->parameters()) as $name) {
            $this->assertTrue($controller->parameters()->has($name));
        }
        $parameters = [];
        /** @var StringParameterInterface $parameter */
        foreach ($controller->parameters()->getIterator() as $name => $parameter) {
            $parameters[$name] = [
                'name' => $name,
                'regex' => $parameter->regex()->__toString(),
                'description' => $parameter->description(),
                'isRequired' => $controller->parameters()->isRequired($name),
            ];
        }
        $this->assertSame(
            $parameters,
            $endpoint->parameters()
        );
    }

    public function testWithDescription(): void
    {
        $description = 'Some description';
        $endpoint = new Endpoint(new GetMethod(), bind(new EndpointTestController()));
        $endpointWithDescription = $endpoint->withDescription($description);
        $this->assertNotSame($endpoint, $endpointWithDescription);
        $this->assertSame($description, $endpointWithDescription->description());
    }

    public function testWithoutWrongParameter(): void
    {
        $bind = bind(new EndpointTestController());
        $this->expectException(OutOfBoundsException::class);
        (new Endpoint(new GetMethod(), $bind))
            ->withoutParameter('0x0');
    }

    public function testWithoutParameter(): void
    {
        $controller = new EndpointTestController();
        $iterator = $controller->parameters()->getIterator();
        $iterator->rewind();
        $key = $iterator->key() ?? 'name';
        $endpoint = (new Endpoint(new GetMethod(), bind($controller)))
            ->withoutParameter($key);
        $this->assertArrayNotHasKey($key, $endpoint->parameters());
    }
}
