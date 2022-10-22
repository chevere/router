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
use Chevere\DataStructure\Traits\MapToArrayTrait;
use Chevere\DataStructure\Traits\MapTrait;
use Chevere\Router\Interfaces\WildcardInterface;
use Chevere\Router\Interfaces\WildcardsInterface;

final class Wildcards implements WildcardsInterface
{
    use MapTrait;
    use MapToArrayTrait;

    /**
     * name => int $pos
     */
    private MapInterface $index;

    private int $pos = -1;

    public function __construct()
    {
        $this->map = new Map();
        $this->index = new Map();
    }

    public function __clone()
    {
        $this->map = clone $this->map;
        $this->index = clone $this->index;
    }

    public function withPut(WildcardInterface $routeWildcard): WildcardsInterface
    {
        $new = clone $this;
        if ($new->index->has($routeWildcard->__toString())) {
            /** @var int $getPos */
            $getPos = $new->index->get($routeWildcard->__toString());
            $new->pos = $getPos;
        } else {
            $new->pos++;
        }
        $new->index = $new->index
            ->withPut($routeWildcard->__toString(), $new->pos);
        $new->map = $new->map
            ->withPut(strval($new->pos), $routeWildcard);

        return $new;
    }

    public function has(string $wildcardName): bool
    {
        return $this->index->has($wildcardName);
    }

    public function get(string $wildcardName): WildcardInterface
    {
        $posStr = strval($this->index->get($wildcardName));
        /** @var WildcardInterface */
        return $this->map->get($posStr);
    }

    public function hasPos(int $pos): bool
    {
        return $this->map->has(strval($pos));
    }

    public function getPos(int $pos): WildcardInterface
    {
        /** @var WildcardInterface */
        return $this->map->get(strval($pos));
    }
}
