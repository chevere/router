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

use Chevere\Message\Message;
use Chevere\Router\Interfaces\IdentifierInterface;
use Chevere\Router\Interfaces\IndexInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Throwable\Errors\TypeError;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use Ds\Map;

final class Index implements IndexInterface
{
    /**
     * [<string>routeName => IdentifierInterface,]
     * @var Map<string, IdentifierInterface>
     */
    private Map $identifiersMap;

    /**
     * [<string>routeName => <string>groupName,]
     * @var Map<string, string>
     */
    private Map $groupsIndex;

    /**
     * [<string>groupName => [<string>routeName],]
     * @var Map<string, string[]>
     */
    private Map $groupsMap;

    public function __construct()
    {
        $this->identifiersMap = new Map();
        $this->groupsIndex = new Map();
        $this->groupsMap = new Map();
    }

    public function withAddedRoute(RouteInterface $route, string $group): IndexInterface
    {
        $new = clone $this;
        $name = $route->path()->__toString();
        $identifier = new Identifier($group, $name);
        if ($new->groupsIndex->hasKey($name)) {
            /** @var string $groupName */
            $groupName = $new->groupsIndex->get($name);

            throw new OverflowException(
                (new Message('Route name %routeName% is already bound to group %groupName%'))
                    ->withCode('%routeName%', $name)
                    ->withCode('%groupName%', $groupName)
            );
        }
        $new->identifiersMap->put($name, $identifier);
        $new->groupsIndex->put($name, $group);
        $names = [];
        if ($new->groupsMap->hasKey($group)) {
            $names = $new->groupsMap->get($group);
        }
        $names[] = $name;
        $new->groupsMap->put($group, $names);

        return $new;
    }

    public function hasRouteName(string $name): bool
    {
        return $this->identifiersMap->hasKey($name);
    }

    /**
     * @throws TypeError
     * @throws OutOfBoundsException
     */
    public function getRouteIdentifier(string $name): IdentifierInterface
    {
        try {
            return $this->identifiersMap->get($name);
        } catch (\OutOfBoundsException $e) {
            throw new OutOfBoundsException(
                (new Message('Route name %routeName% not found'))
                    ->withCode('%routeName%', $name)
            );
        }
    }

    public function hasGroup(string $group): bool
    {
        return $this->groupsMap->hasKey($group);
    }

    /**
     * @throws TypeError
     * @throws OutOfBoundsException
     */
    public function getGroupRouteNames(string $group): array
    {
        try {
            return $this->groupsMap->get($group);
        } catch (\OutOfBoundsException) {
            throw new OutOfBoundsException(
                (new Message('Group %group% not found'))
                    ->withCode('%group%', $group)
            );
        }
    }

    /**
     * @throws TypeError
     * @throws OutOfBoundsException
     */
    public function getRouteGroup(string $name): string
    {
        try {
            return $this->groupsIndex->get($name);
        } catch (\OutOfBoundsException) {
            throw new OutOfBoundsException(
                (new Message('Group %group% not found'))
                    ->withCode('%group%', $name)
            );
        }
    }

    public function toArray(): array
    {
        $array = [];
        /** @var IdentifierInterface $routeIdentifier */
        foreach ($this->identifiersMap as $routePath => $routeIdentifier) {
            $array[$routePath] = $routeIdentifier->toArray();
        }

        return $array;
    }
}
