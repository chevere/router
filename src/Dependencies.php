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
use Chevere\Parameter\Arguments;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Parameter\Parameters;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use LogicException;
use OutOfBoundsException;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Stringable;
use Throwable;
use TypeError;
use function Chevere\Message\message;
use function Chevere\Parameter\getType;
use function Chevere\Parameter\reflectionToParameters;

final class Dependencies implements DependenciesInterface
{
    private ParametersInterface $parameters;

    /**
     * @var array<string, string>
     */
    private array $requirer = [];

    /**
     * [<string>className => ParametersInterface,]
     * @var Map<ParametersInterface>
     */
    private Map $map;

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
        foreach ($route as $item) {
            $new->addRoute($item);
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

    public function extract(string $className, ContainerInterface $container): array
    {
        if (! $this->has($className)) {
            return [];
        }
        $parameters = $this->get($className);
        $extracted = [];
        foreach ($parameters->keys() as $name) {
            if (! $container->has($name)) {
                throw new OutOfBoundsException(
                    "Dependency `{$name}` not defined in container"
                );
            }
            $extracted[$name] = $container->get($name);
        }

        /** @var array<string, mixed> */
        return (new Arguments($parameters, $extracted))->toArray();
    }

    public function assert(ContainerInterface $container): void
    {
        $errors = [];
        foreach ($this->parameters as $name => $parameter) {
            $name = strval($name);
            $hasArgument = $container->has($name);
            if (! $hasArgument
                && $this->parameters->optionalKeys()->contains($name)
            ) {
                continue;
            }
            $requirer = $this->requirer($name);
            $fileLine = $this->locate($requirer);
            if (! $hasArgument) {
                $errors[] = message(
                    <<<PLAIN
                    Missing argument `%key%` as previously defined by `%requirer%` in %fileLine%
                    PLAIN,
                    key: $name,
                    requirer: $requirer,
                    fileLine: $fileLine,
                );

                continue;
            }

            try {
                /** @var mixed $value */
                $value = $container->get($name);
                // @phpstan-ignore-next-line
                $parameter($value);
            } catch (Throwable) {
                $value ??= null;
                $provided = getType($value);
                if (is_object($value)) {
                    $provided = get_class($value);
                }
                $errors[] = message(
                    <<<PLAIN
                    Argument `{$name}` provided as `%provided%` is not compatible with `%expected%` as previously defined by `%requirer%` in %fileLine%
                    PLAIN,
                    provided: $provided,
                    expected: $parameter->type()->typeHinting(),
                    requirer: $requirer,
                    fileLine: $fileLine,
                );
            }
        }
        if ($errors !== []) {
            $message = $this->errorMessage($errors);

            throw new LogicException($message);
        }
    }

    public function requirer(string $name): string
    {
        if (array_key_exists($name, $this->requirer)) {
            return $this->requirer[$name];
        }

        throw new OutOfBoundsException(
            "Dependency `{$name}` not defined"
        );
    }

    private function locate(string $className): string
    {
        $reflector = new ReflectionMethod($className, '__construct');

        return $reflector->getFileName() . ':' . $reflector->getStartLine();
    }

    /**
     * @param array<string|Stringable> $errors
     */
    private function errorMessage(array $errors): string
    {
        return count($errors) === 1
            ? strval($errors[0])
            : implode(
                "\n\n",
                array_map(
                    fn ($i, $error) => '- [' . ($i + 1) . ']: ' . $error,
                    array_keys($errors),
                    $errors
                )
            );
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
        $errors = [];
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
                $requirer = $this->requirer($name);
                $fileLine = $this->locate($requirer);
                $errors[] = message(
                    <<<PLAIN
                    Variable `\${$name}` defined as `%provided%` is not compatible with `%expected%` as previously defined by `%requirer%` in %fileLine%
                    PLAIN,
                    name: $name,
                    provided: $parameter->type()->typeHinting(),
                    expected: $existing->type()->typeHinting(),
                    requirer: $requirer,
                    fileLine: $fileLine,
                );
            }
            $parameters = $parameters->without($name);
        }
        if ($errors !== []) {
            $message = $this->errorMessage($errors);

            throw new TypeError($message);
        }
        $this->parameters = $this->parameters->withMerge($parameters);
        foreach ($parameters->keys() as $key) {
            $this->requirer[$key] = $className;
        }
    }
}
