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

use Chevere\Parameter\Interfaces\TypeInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Describes the component in charge of wrap ResponseInterface and its routed result.
 */
interface RoutedInterface
{
    /**
     * Return an instance with the specified $raw.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified $raw.
     */
    public function withRaw(mixed $raw): self;

    /**
     * Return an instance with the specified $throwable.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified $throwable.
     */
    public function withThrowable(Throwable $throwable): self;

    /**
     * Provides access to the ResponseInterface instance.
     */
    public function response(): ResponseInterface;

    /**
     * Provides access to the BindInterface instance.
     */
    public function bind(): BindInterface;

    /**
     * Provides access to the TypeInterface instance for the raw result.
     */
    public function type(): TypeInterface;

    /**
     * Provides access to the raw routed result.
     */
    public function raw(): mixed;

    public function throwable(): ?Throwable;
}
