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
use Chevere\DataStructure\Traits\MapTrait;
use Chevere\Http\Interfaces\MiddlewareNameInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Http\MiddlewareName;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use OutOfBoundsException;
use OverflowException;
use function Chevere\Http\middlewares;
use function Chevere\Message\message;

final class Routes implements RoutesInterface
{
    /**
     * @template-use MapTrait<RouteInterface>
     */
    use MapTrait;

    /**
     * @var MapInterface<string>
     */
    private MapInterface $names;

    /**
     * @throws OverflowException
     */
    public function withRoute(RouteInterface ...$route): RoutesInterface
    {
        $new = clone $this;
        $new->names ??= new Map();
        foreach ($route as $item) {
            $id = $item->path()->regex()->noDelimiters();
            $new->assertNoOverflow($id, $item);
            $new->names = $new->names
                ->withPut($item->name(), $id);
            $new->map = $new->map->withPut($id, $item);
        }

        return $new;
    }

    public function withRoutes(RoutesInterface ...$routes): RoutesInterface
    {
        $new = clone $this;
        foreach ($routes as $item) {
            foreach ($item as $route) {
                $new = $new->withRoute($route);
            }
        }

        return $new;
    }

    public function withPrependMiddleware(MiddlewaresInterface|MiddlewareNameInterface|string ...$middleware): RoutesInterface
    {
        $new = clone $this;
        $new->addMiddleware('withPrepend', $new->getMiddlewares(...$middleware));

        return $new;
    }

    public function withAppendMiddleware(MiddlewaresInterface|MiddlewareNameInterface|string ...$middleware): RoutesInterface
    {
        $new = clone $this;
        $new->addMiddleware('withAppend', $new->getMiddlewares(...$middleware));

        return $new;
    }

    public function has(string ...$path): bool
    {
        return $this->map->has(...$path);
    }

    /**
     * @throws OutOfBoundsException
     */
    public function get(string $path): RouteInterface
    {
        /** @return RouteInterface */
        return $this->map->get($path);
    }

    private function getMiddlewares(MiddlewaresInterface|MiddlewareNameInterface|string ...$middleware): MiddlewaresInterface
    {
        $middlewares = middlewares();
        foreach ($middleware as $argument) {
            if ($argument instanceof MiddlewareNameInterface) {
                $middlewares = $middlewares->withAppend($argument);

                continue;
            }
            if ($argument instanceof MiddlewaresInterface) {
                $middlewares = $middlewares->withAppend(...$argument);

                continue;
            }
            $middlewares = $middlewares->withAppend(new MiddlewareName($argument));
        }

        return $middlewares;
    }

    private function addMiddleware(string $method, MiddlewaresInterface $middlewares): void
    {
        foreach ($this->getIterator() as $name => $route) {
            foreach ($route->endpoints() as $endpoint) {
                $collector = middlewares();
                foreach ($middlewares as $middlewareName) {
                    if ($route->excluded()->has($middlewareName)) {
                        continue;
                    }
                    $collector = $collector->withAppend($middlewareName);
                }
                $finalMiddlewares = $endpoint->bind()->middlewares()->{$method}(
                    ...$collector->getIterator()
                );
                $bind = $endpoint->bind()->withMiddlewares($finalMiddlewares);
                $finalEndpoint = new Endpoint($endpoint->method(), $bind);
                $route = $route
                    ->withoutEndpoint($endpoint->method())
                    ->withEndpoint($finalEndpoint);
            }
            $this->map = $this->map->withPut($name, $route);
        }
    }

    private function assertNoOverflow(string $path, RouteInterface $route): void
    {
        if ($route->name() !== '' && $this->names->has($route->name())) {
            throw new OverflowException(
                code: static::EXCEPTION_CODE_TAKEN_NAME,
                message: (string) message(
                    'Named route %name% has been already taken.',
                    name: $route->name()
                )
            );
        }
        if ($this->map->has($path)) {
            throw new OverflowException(
                code: static::EXCEPTION_CODE_TAKEN_PATH,
                message: (string) message(
                    'Route %path% has been already taken.',
                    path: $route->path()->__toString()
                )
            );
        }
    }
}
