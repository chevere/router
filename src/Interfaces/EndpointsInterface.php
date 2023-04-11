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

namespace Chevere\Router\Interfaces;

use Chevere\DataStructure\Interfaces\MappedInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Iterator;

/**
 * Describes the component in charge of collecting objects implementing `EndpointInterface`.
 *
 * @extends MappedInterface<EndpointInterface>
 */
interface EndpointsInterface extends MappedInterface
{
    /**
     * Return an instance with the specified `$endpoint` defined.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `$endpoint` defined.
     */
    public function withPut(EndpointInterface ...$endpoint): self;

    /**
     * Return an instance with the specified `$key` removed.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `$key` removed.
     */
    public function without(string ...$key): self;

    /**
     * Returns a boolean indicating whether the instance has `$key`.
     */
    public function has(string $key): bool;

    /**
     * Provides access to the RouteEndpointInterface identified by `$key`.
     *
     * @throws OutOfBoundsException
     */
    public function get(string $key): EndpointInterface;

    /**
     * @return Iterator<string, EndpointInterface>
     */
    public function getIterator(): Iterator;
}
