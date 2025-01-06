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

use Chevere\DataStructure\Interfaces\MapInterface;
use Chevere\DataStructure\Map;
use Chevere\Parameter\Arguments;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Parameter\Parameters;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use ReflectionMethod;
use Throwable;
use TypeError;
use function Chevere\Parameter\reflectionToParameters;

final class Dependencies implements DependenciesInterface
{
    private ParametersInterface $parameters;

    /**
     * [<string>className => ParametersInterface,]
     * @var MapInterface<ParametersInterface>
     */
    private MapInterface $map;

    public function __construct(?RoutesInterface $routes = null)
    {
        $this->parameters = new Parameters();
        $this->map = new Map();
        foreach ($routes ?? [] as $route) {
            $this->addRoute($route);
        }
    }

    public function withRoute(RouteInterface ...$route): self
    {
        $new = clone $this;
        foreach ($route as $route) {
            $this->addRoute($route);
        }

        return $new;
    }

    public function parameters(): ParametersInterface
    {
        return $this->parameters;
    }

    public function has(string $className): bool
    {
        return $this->map->has($className);
    }

    public function get(string $className): ParametersInterface
    {
        /** @var ParametersInterface */
        return $this->map->get($className);
    }

    public function extract(string $className, array $container): array
    {
        if (! $this->has($className)) {
            return [];
        }

        return (new Arguments(
            $this->get($className),
            $container
        ))->toArray();
    }

    private function addRoute(RouteInterface $route): void
    {
        foreach ($route->endpoints() as $endpoint) {
            $controller = $endpoint->bind()->controllerName()->__toString();
            $this->handleParameters($controller);
            $this->setMiddleware($endpoint);
        }
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
        $reflection = new ReflectionMethod($className, '__construct');
        $parameters = reflectionToParameters($reflection);
        if (! $this->map->has($className)) {
            $this->map = $this->map->withPut($className, $parameters);
        }
        foreach ($parameters as $name => $parameter) {
            if (! $this->parameters->has($name)) {
                continue;
            }
            $existing = $this->parameters->get($name);

            try {
                $existing->assertCompatible($parameter);
            } catch (Throwable $e) {
                throw new TypeError(
                    <<<PLAIN
                    Incompatible dependency type for variable `\${$name}` at `{$className}::__construct` previously defined as type `{$existing->type()->typeHinting()}`
                    PLAIN
                );
            }
            $parameters = $parameters->without($name);
        }
        $this->parameters = $this->parameters->withMerge($parameters);
    }
}
