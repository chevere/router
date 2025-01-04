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

/**
 * Describes the component in charge of wrap ResponseInterface and its routed result.
 */
interface RoutedInterface
{
    public function response(): ResponseInterface;

    public function view(): string;

    public function raw(): mixed;
}
