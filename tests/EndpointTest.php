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
use Chevere\Tests\_resources\EndpointTestController;
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
    }
}
