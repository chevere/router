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
use Chevere\Router\Interfaces\RoutedInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class Routed implements RoutedInterface
{
    private Throwable $throwable;

    private bool $hasThrowable = false;

    public function __construct(
        private ResponseInterface $response,
        private BindInterface $bind,
        private mixed $return = null,
    ) {
    }

    public function withThrowable(Throwable $throwable): RoutedInterface
    {
        $new = clone $this;
        $new->throwable = $throwable;
        $new->hasThrowable = true;

        return $new;
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }

    public function bind(): BindInterface
    {
        return $this->bind;
    }

    public function hasThrowable(): bool
    {
        return $this->hasThrowable;
    }

    public function return(): mixed
    {
        return $this->return;
    }

    public function throwable(): Throwable
    {
        return $this->throwable;
    }
}
