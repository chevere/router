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

use Chevere\DataStructure\Traits\MapToArrayTrait;
use Chevere\DataStructure\Traits\MapTrait;
use Chevere\Router\Interfaces\WildcardInterface;
use Chevere\Router\Interfaces\WildcardsInterface;

final class Wildcards implements WildcardsInterface
{
    /**
     * @template-use MapTrait<WildcardInterface>
     */
    use MapTrait;

    use MapToArrayTrait;

    public function withPut(WildcardInterface $wildcard): WildcardsInterface
    {
        $new = clone $this;
        $new->map = $new->map
            ->withPut(...[
                $wildcard->__toString() => $wildcard,
            ]);

        return $new;
    }

    public function has(string $name): bool
    {
        return $this->map->has($name);
    }

    public function get(string $name): WildcardInterface
    {
        /** @var WildcardInterface */
        return $this->map->get($name);
    }
}
