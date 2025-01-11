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
     * Provides access to the TypeInterface instance for the raw result.
     */
    public function type(): TypeInterface;

    /**
     * Provides access to the raw routed result.
     */
    public function raw(): mixed;
}
