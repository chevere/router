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

use Chevere\Caller\Interfaces\CallerInterface;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;

/**
 * Describes the component in charge of defining a route.
 */
interface RouteInterface
{
    public function name(): string;

    public function path(): PathInterface;

    /**
     * Return an instance with the specified added `$endpoint`.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified added `$endpoint`.
     */
    public function withEndpoint(EndpointInterface $endpoint): self;

    /**
     * Return an instance with endpoints removed for matching `$method`.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains endpoints removed for matching `$method`.
     *
     * This method should allow to override any previous `$endpoint`.
     */
    public function withoutEndpoint(MethodInterface $method): self;

    public function endpoints(): EndpointsInterface;

    public function caller(): CallerInterface;

    public function excluded(): MiddlewaresInterface;
}
