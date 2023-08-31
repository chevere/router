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

use Chevere\Http\Interfaces\ControllerNameInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Router\Interfaces\BindInterface;

final class Bind implements BindInterface
{
    private string $view;

    public function __construct(
        private ControllerNameInterface $controllerName,
        private MiddlewaresInterface $middlewares,
    ) {
        $this->view = '';
    }

    public function controllerName(): ControllerNameInterface
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

    public function withView(string $view): BindInterface
    {
        $new = clone $this;
        $new->view = $view;

        return $new;
    }

    public function withMiddlewares(MiddlewaresInterface $middlewares): BindInterface
    {
        $new = clone $this;
        $new->middlewares = $middlewares;

        return $new;
    }
}
