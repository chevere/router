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
use Chevere\Parameter\Interfaces\ObjectParameterInterface;
use Chevere\Parameter\Interfaces\ParametersAccessInterface;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Router\Exceptions\ContainerException;
use Chevere\Router\Exceptions\ContainerNotFoundException;
use Chevere\Router\Interfaces\ContainerInterface;
use ReflectionMethod;
use Throwable;
use function Chevere\Parameter\getParameters;
use function Chevere\Parameter\reflectionToParameters;

final class Container implements ContainerInterface
{
    /**
     * @template-use MapTrait<mixed>
     */
    use MapTrait;

    public function __construct(
        mixed ...$entries
    ) {
        $this->map = new Map(...$entries);
    }

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new ContainerNotFoundException();
        }

        try {
            return $this->map->get($id);
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            throw new ContainerException();
            // @codeCoverageIgnoreEnd
        }
    }

    public function has(string $id): bool
    {
        return $this->map->has($id);
    }

    public function withAutoInject(
        ParametersInterface|ParametersAccessInterface $dependencies,
        string ...$ignore
    ): ContainerInterface {
        $new = clone $this;
        $parameters = getParameters($dependencies);
        $ignore = array_values(array_intersect($ignore, $parameters->keys()));
        $missingDeps = array_diff(
            $parameters->keys(),
            $new->keys(),
            $ignore
        );
        $failures = [];
        foreach ($missingDeps as $missingDep) {
            $arguments = [];
            $parameter = $parameters->has($missingDep)
                ? $parameters->get($missingDep)
                : null;
            if (! ($parameter instanceof ObjectParameterInterface)) {
                $failures[] = [$missingDep, "Parameter {$missingDep} is not an object type"];

                continue;
            }
            $className = $parameter->type()->typeHinting();
            if (method_exists($className, '__construct')) {
                $reflection = new ReflectionMethod($className, '__construct');
                $reflectionParameters = reflectionToParameters($reflection);
                if (count($reflectionParameters) > 0) {
                    try {
                        $arguments = $reflectionParameters(...iterator_to_array($new))
                            ->toArray();
                    } catch (Throwable $e) {
                        $failures[] = [$missingDep, "Failed to resolve dependencies for {$className}: {$e->getMessage()}"];

                        continue;
                    }
                }
            }

            try {
                $new = $new->with(
                    ...[
                        $missingDep => new $className(...$arguments),
                    ]
                );
            } catch (Throwable $e) {
                $failures[] = [$missingDep, "Failed to instantiate {$className}: {$e->getMessage()}"];
            }
        }
        if ($failures !== []) {
            $lines = [];
            foreach ($failures as [$param, $message]) {
                $lines[] = "[{$param}]: {$message}";
            }

            throw new ContainerException(implode("\n", $lines));
        }

        return $new;
    }

    public function with(mixed ...$entry): ContainerInterface
    {
        $new = clone $this;
        foreach ($entry as $name => $value) {
            $new->map = $new->map->withPut(strval($name), $value);
        }

        return $new;
    }

    public function without(string ...$entry): ContainerInterface
    {
        $new = clone $this;
        $new->map = $new->map->without(...$entry);

        return $new;
    }

    public function extract(string $className): array
    {
        $reflection = new ReflectionMethod($className, '__construct');
        $dependencies = reflectionToParameters($reflection);
        $new = $this->withAutoInject($dependencies);
        $extra = array_diff($this->keys(), $dependencies->keys());

        return iterator_to_array(
            $new->without(...$extra)
        );
    }
}
