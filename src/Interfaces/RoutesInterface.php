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
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Iterator;

/**
 * Describes the component in charge of collecting objects implementing `RouteInterface`.
 *
 * @extends MappedInterface<RouteInterface>
 */
interface RoutesInterface extends MappedInterface
{
    public const EXCEPTION_CODE_TAKEN_NAME = 110;

    public const EXCEPTION_CODE_TAKEN_PATH = 100;

    /**
     * Return an instance with the specified `$route` added.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `$routes` added.
     */
    public function withRoute(RouteInterface ...$route): self;

    /**
     * Return an instance with the specified `$routes` added.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `$routes` added.
     */
    public function withRoutes(self ...$routes): self;

    /**
     * Return an instance with the specified `$middleware` prepended to each route.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `$middleware` prepended to each route.
     */
    public function withPrependMiddleware(MiddlewaresInterface $middleware): self;

    /**
     * Return an instance with the specified `$middleware` appended to each route.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `$middleware` appended to each route.
     */
    public function withAppendMiddleware(MiddlewaresInterface $middleware): self;

    /**
     * Indicates whether the instance has routable(s) identified by its `$path`.
     */
    public function has(string ...$path): bool;

    /**
     * Returns the routable identified by its `$path`.
     *
     * @throws OutOfBoundsException
     */
    public function get(string $path): RouteInterface;

    /**
     * @return Iterator<string, RouteInterface>
     */
    public function getIterator(): Iterator;
}
