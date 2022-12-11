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
use Chevere\Message\Message;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\EndpointsInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;

final class Endpoints implements EndpointsInterface
{
    use MapTrait;

    public function withPut(EndpointInterface $routeEndpoint): EndpointsInterface
    {
        $new = clone $this;
        $new->map = $new->map->withPut(
            ...[
                $routeEndpoint->method()->name() => $routeEndpoint,
            ]
        );

        return $new;
    }

    public function has(string $key): bool
    {
        return $this->map->has($key);
    }

    public function get(string $key): EndpointInterface
    {
        try {
            /** @var EndpointInterface $return */
            $return = $this->map->get($key);
        } catch (\OutOfBoundsException $e) {
            throw new OutOfBoundsException(
                (new Message('Key %key% not found'))
                    ->withCode('%key%', $key)
            );
        }

        return $return;
    }
}
