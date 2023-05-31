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

use Chevere\DataStructure\Traits\MapTrait;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\EndpointsInterface;

final class Endpoints implements EndpointsInterface
{
    /**
     * @template-use MapTrait<EndpointInterface>
     */
    use MapTrait;

    public function withPut(EndpointInterface ...$endpoint): EndpointsInterface
    {
        $new = clone $this;
        foreach ($endpoint as $item) {
            $new->map = $new->map->withPut(
                $item->method()->name(),
                $item,
            );
        }

        return $new;
    }

    public function without(string ...$key): EndpointsInterface
    {
        $new = clone $this;
        $new->map = $new->map->without(...$key);

        return $new;
    }

    public function has(string $key): bool
    {
        return $this->map->has($key);
    }

    public function get(string $key): EndpointInterface
    {
        return $this->map->get($key);
    }
}
