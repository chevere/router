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

use Chevere\Parameter\Interfaces\TypeInterface;
use Chevere\Parameter\Type;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\RoutedInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function Chevere\Parameter\getType;

final class Routed implements RoutedInterface
{
    private TypeInterface $type;

    private mixed $raw;

    private Throwable $throwable;

    public function __construct(
        private ResponseInterface $response,
        private BindInterface $bind,
    ) {
    }

    public function withRaw(mixed $raw): RoutedInterface
    {
        $new = clone $this;
        $new->raw = $raw;
        $new->type = new Type(getType($raw));

        return $new;
    }

    public function withThrowable(Throwable $throwable): RoutedInterface
    {
        $new = clone $this;
        $new->throwable = $throwable;

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

    public function type(): TypeInterface
    {
        return $this->type;
    }

    public function raw(): mixed
    {
        return $this->raw;
    }

    public function throwable(): Throwable
    {
        return $this->throwable;
    }
}
