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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Describes the component in charge of wrap ResponseInterface and its routed result.
 */
interface RoutedInterface
{
    /**
     * Provides access to the ResponseInterface instance.
     */
    public function response(): ResponseInterface;

    /**
     * Provides access to the BindInterface instance.
     */
    public function bind(): BindInterface;

    /**
     * Provides access to the controller __invoke return value.
     */
    public function return(): mixed;

    /**
     * Provides access to the Throwable instance, if it exists.
     */
    public function throwable(): ?Throwable;

    /**
     * Provides access to the server request as the middleware queue left it.
     *
     * When a middleware returns early, this is the request that middleware received.
     */
    public function request(): ServerRequestInterface;
}
