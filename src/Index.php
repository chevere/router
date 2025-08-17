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
use Chevere\Router\Interfaces\IdentifierInterface;
use Chevere\Router\Interfaces\IndexInterface;
use Chevere\Router\Interfaces\RouteInterface;
use OutOfBoundsException;
use OverflowException;
use function Chevere\Message\message;

final class Index implements IndexInterface
{
    /**
     * [<string>routeName => IdentifierInterface,]
     * @var Map<IdentifierInterface>
     */
    private Map $identifiersMap;

    /**
     * [<string>routeName => <string>groupName,]
     * @var Map<string>
     */
    private Map $groupsIndex;

    /**
     * [<string>groupName => [<string>routeName],]
     * @var Map<string[]>
     */
    private Map $groupsMap;

    public function __construct()
    {
        $this->identifiersMap = new Map();
        $this->groupsIndex = new Map();
        $this->groupsMap = new Map();
    }

    public function withRoute(RouteInterface $route, string $group = ''): IndexInterface
    {
        $new = clone $this;
        $id = $route->path()->regex()->noDelimiters();
        $identifier = new Identifier($group, $id);
        if ($new->groupsIndex->has($id)) {
            /** @var string $groupName */
            $groupName = $new->groupsIndex->get($id);

            throw new OverflowException(
                (string) message(
                    'Route` %path%` (regex `%id%`) is already bound to group `%group%`',
                    path: $route->path()->__toString(),
                    id: $id,
                    group: $groupName
                )
            );
        }
        $new->identifiersMap = $new->identifiersMap->withPut($id, $identifier);
        $new->groupsIndex = $new->groupsIndex->withPut($id, $group);
        $names = [];
        if ($new->groupsMap->has($group)) {
            $names = $new->groupsMap->get($group);
        }
        $names[] = $id;
        $new->groupsMap = $new->groupsMap->withPut($group, $names);

        return $new;
    }

    public function hasRouteName(string $name): bool
    {
        return $this->identifiersMap->has($name);
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getRouteIdentifier(string $name): IdentifierInterface
    {
        return $this->identifiersMap->get($name);
    }

    public function hasGroup(string $group): bool
    {
        return $this->groupsMap->has($group);
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getGroupRouteNames(string $group): array
    {
        return $this->groupsMap->get($group);
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getRouteGroup(string $name): string
    {
        return $this->groupsIndex->get($name);
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->identifiersMap as $path => $identifier) {
            $array[$path] = $identifier->toArray();
        }

        /** @phpstan-ignore-next-line */
        return $array;
    }
}
