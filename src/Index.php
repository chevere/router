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
use Chevere\Message\Message;
use Chevere\Router\Interfaces\IdentifierInterface;
use Chevere\Router\Interfaces\IndexInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Throwable\Errors\TypeError;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;

final class Index implements IndexInterface
{
    /**
     * [<string>routeName => IdentifierInterface,]
     * @var MapInterface<IdentifierInterface>
     */
    private MapInterface $identifiersMap;

    /**
     * [<string>routeName => <string>groupName,]
     * @var MapInterface<string>
     */
    private MapInterface $groupsIndex;

    /**
     * [<string>groupName => [<string>routeName],]
     * @var MapInterface<string[]>
     */
    private MapInterface $groupsMap;

    public function __construct()
    {
        $this->identifiersMap = new Map();
        $this->groupsIndex = new Map();
        $this->groupsMap = new Map();
    }

    public function withAddedRoute(RouteInterface $route, string $group = ''): IndexInterface
    {
        $new = clone $this;
        $id = $route->path()->regex()->noDelimiters();
        $identifier = new Identifier($group, $id);
        if ($new->groupsIndex->has($id)) {
            /** @var string $groupName */
            $groupName = $new->groupsIndex->get($id);

            throw new OverflowException(
                (new Message('Route %path% (regex %id%) is already bound to group %group%'))
                    ->withCode('%path%', $route->path()->__toString())
                    ->withCode('%id%', $id)
                    ->withCode('%group%', $groupName)
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
     * @throws TypeError
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
     * @throws TypeError
     * @throws OutOfBoundsException
     */
    public function getGroupRouteNames(string $group): array
    {
        return $this->groupsMap->get($group);
    }

    /**
     * @throws TypeError
     * @throws OutOfBoundsException
     */
    public function getRouteGroup(string $name): string
    {
        return $this->groupsIndex->get($name);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->identifiersMap as $path => $identifier) {
            $array[$path] = $identifier->toArray();
        }

        return $array;
    }
}
