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
use Chevere\Router\Interfaces\RoutedInterface;
use Psr\Http\Message\ResponseInterface;
use function Chevere\Parameter\getType;

final class Routed implements RoutedInterface
{
    private TypeInterface $type;

    public function __construct(
        private ResponseInterface $response,
        private string $view,
        private mixed $raw
    ) {
        $this->type = new Type(getType($raw));
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function type(): TypeInterface
    {
        return $this->type;
    }

    public function raw(): mixed
    {
        return $this->raw;
    }
}
