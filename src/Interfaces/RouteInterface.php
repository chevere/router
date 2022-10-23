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

use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\WildcardConflictException;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Describes the component in charge of defining a route.
 */
interface RouteInterface
{
    public function name(): string;

    /**
     * Provides access to the `$path` instance.
     */
    public function path(): PathInterface;

    /**
     * Provides access to view.
     */
    public function view(): string;

    /**
     * Provides access to the middleware.
     *
     * @return array<MiddlewareInterface>
     */
    public function middleware(): array;

    /**
     * Return an instance with the specified added `$middleware`.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified added `$middleware`.
     */
    public function withMiddleware(MiddlewareInterface ...$middleware): self;

    /**
     * Return an instance with the specified added `$endpoint`.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified added `$endpoint`.
     *
     * This method should allow to override any previous `$endpoint`.
     *
     * @throws OverflowException
     * @throws EndpointConflictException
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     * @throws WildcardConflictException
     */
    public function withEndpoint(EndpointInterface $endpoint): self;

    /**
     * Provides access to the endpoints instance.
     */
    public function endpoints(): EndpointsInterface;
}