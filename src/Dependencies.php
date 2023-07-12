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

namespace Chevere\Router;

use Chevere\DataStructure\Map;
use Chevere\DataStructure\Traits\MapTrait;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use function Chevere\Parameter\methodParameters;

final class Dependencies implements DependenciesInterface
{
    /**
     * @template-use MapTrait<ParametersInterface>
     */
    use MapTrait;

    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $array;

    public function __construct(
        RoutesInterface $routes
    ) {
        $this->array = [];
        $this->map = new Map();
        foreach ($routes as $route) {
            foreach ($route->endpoints() as $endpoint) {
                $controller = $endpoint->bind()->controllerName()->__toString();
                $this->handleParameters($controller);
                $this->setMiddleware($endpoint);
            }
        }
    }

    /**
     * @throws OutOfBoundsException
     */
    public function get(string $className): ParametersInterface
    {
        /** @var ParametersInterface */
        return $this->map->get($className);
    }

    public function toArray(): array
    {
        return $this->array;
    }

    private function setMiddleware(EndpointInterface $endpoint): void
    {
        $middlewares = $endpoint->bind()->middlewares();
        foreach ($middlewares as $middlewareName) {
            $middleware = $middlewareName->__toString();
            $this->handleParameters($middleware);
        }
    }

    private function handleParameters(string $className): void
    {
        if (! method_exists($className, '__construct')) {
            return;
        }
        $parameters = methodParameters($className, '__construct');
        $array = [];
        foreach ($parameters as $name => $parameter) {
            $array[$name] = $parameter->schema() + [
                'required' => $parameters->isRequired($name),
            ];
        }
        $this->array[$className] = $array;
        $this->map = $this->map->withPut($className, $parameters);
    }
}
