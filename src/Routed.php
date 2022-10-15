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

use Chevere\Controller\Interfaces\ControllerInterface;
use Chevere\Router\Interfaces\RoutedInterface;

final class Routed implements RoutedInterface
{
    /**
     * @param array<string, string> $arguments
     */
    public function __construct(
        private ControllerInterface $controller,
        private array $arguments
    ) {
    }

    public function controller(): ControllerInterface
    {
        return $this->controller;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }
}
