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

use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\HttpController\Interfaces\HttpControllerNameInterface;
use Chevere\Router\Interfaces\BindInterface;

final class Bind implements BindInterface
{
    public function __construct(
        private HttpControllerNameInterface $controllerName,
        private MiddlewaresInterface $middlewares,
        private string $view,
    ) {
    }

    public function controllerName(): HttpControllerNameInterface
    {
        return $this->controllerName;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function middlewares(): MiddlewaresInterface
    {
        return $this->middlewares;
    }

    public function withMiddlewares(MiddlewaresInterface $middlewares): BindInterface
    {
        $new = clone $this;
        $new->middlewares = $middlewares;

        return $new;
    }
}
