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
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Message\Message;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Errors\TypeError;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;

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

    public function withPrependMiddleware(MiddlewaresInterface $middleware): RoutesInterface
    {
        $new = clone $this;
        $new->addMiddleware('withPrepend', $middleware);

        return $new;
    }

    public function withAppendMiddleware(MiddlewaresInterface $middleware): RoutesInterface
    {
        $new = clone $this;
        $new->addMiddleware('withAppend', $middleware);

        return $new;
    }

    public function has(string ...$path): bool
    {
        return $this->map->has(...$path);
    }

    /**
     * @throws TypeError
     * @throws OutOfBoundsException
     */
    public function get(string $path): RouteInterface
    {
        /** @return RouteInterface */
        return $this->map->get($path);
    }

    private function addMiddleware(string $method, MiddlewaresInterface $middleware): void
    {
        foreach ($this->getIterator() as $name => $route) {
            foreach ($route->endpoints() as $endpoint) {
                $finalMiddlewares = $endpoint->bind()->middlewares()->{$method}(
                    ...$middleware->getIterator()
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
        if ($route->name() !== null && $this->names->has($route->name())) {
            throw new OverflowException(
                code: static::EXCEPTION_CODE_TAKEN_NAME,
                message: (new Message('Named route %name% has been already taken.'))
                    ->withCode('%name%', $route->name())
            );
        }
        if ($this->map->has($path)) {
            throw new OverflowException(
                code: static::EXCEPTION_CODE_TAKEN_PATH,
                message: (new Message('Route %path% has been already taken.'))
                    ->withCode('%path%', $route->path()->__toString())
            );
        }
    }
}
