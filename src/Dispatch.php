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

use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\DispatchInterface;

final class Dispatch implements DispatchInterface
{
    /**
     * @param array<string, string> $arguments
     */
    public function __construct(
        private BindInterface $bind,
        private array $arguments,
    ) {
    }

    public function bind(): BindInterface
    {
        return $this->bind;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }
}
