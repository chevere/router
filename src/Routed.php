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

use Chevere\Router\Interfaces\RoutedInterface;
use Psr\Http\Message\ResponseInterface;

final class Routed implements RoutedInterface
{
    public function __construct(
        private ResponseInterface $response,
        private string $view,
        private mixed $raw = null
    ) {
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function raw(): mixed
    {
        return $this->raw;
    }
}
