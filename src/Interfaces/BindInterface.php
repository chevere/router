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

use Chevere\Http\Interfaces\ControllerNameInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;

/**
 * Describes the component in charge of binding a ControllerNameInterface
 * to a view.
 */
interface BindInterface
{
    public function controllerName(): ControllerNameInterface;

    public function view(): string;

    public function middlewares(): MiddlewaresInterface;

    public function withView(string $view): self;

    public function withMiddlewares(MiddlewaresInterface $middlewares): self;
}
