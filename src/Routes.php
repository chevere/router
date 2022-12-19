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
use Chevere\Message\Message;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Errors\TypeError;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;

final class Routes implements RoutesInterface
{
    use MapTrait;

    /**
     * @var Map<string, RouteInterface>
     */
    private Map $map;

    /**
     * @var Map<string, string>
     */
    private Map $names;

    public function withAdded(RouteInterface ...$routes): RoutesInterface
    {
        $new = clone $this;
        $new->names ??= new Map();
        foreach ($routes as $route) {
            $key = $route->path()->__toString();
            $new->assertRoute($key, $route);
            $new->names = $new->names
                ->withPut(...[
                    $route->name() => $key,
                ]);
            $new->map = $new->map->withPut(...[
                $key => $route,
            ]);
        }

        return $new;
    }

    public function has(string ...$paths): bool
    {
        return $this->map->has(...$paths);
    }

    /**
     * @throws TypeError
     * @throws OutOfBoundsException
     */
    public function get(string $path): RouteInterface
    {
        return $this->map->get($path);
    }

    private function assertRoute(string $path, RouteInterface $route): void
    {
        if ($this->names->has($route->name())) {
            throw new OverflowException(
                code: static::EXCEPTION_CODE_TAKEN_NAME,
                message: (new Message('Named route %name% has been already taken.'))
                    ->withCode('%name%', $route->name())
            );
        }
        if ($this->map->has($path)) {
            throw new OverflowException(
                code: static::EXCEPTION_CODE_TAKEN_PATH,
                message: (new Message('Route path %path% has been already taken.'))
                    ->withCode('%path%', $route->name())
            );
        }
    }
}
