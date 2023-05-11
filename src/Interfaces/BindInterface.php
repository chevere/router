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

use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\HttpController\Interfaces\HttpControllerNameInterface;

/**
 * Describes the component in charge of binding a HttpControllerNameInterface
 * to a view.
 */
interface BindInterface
{
    public function controllerName(): HttpControllerNameInterface;

    public function view(): string;

    public function middlewares(): MiddlewaresInterface;

    public function withMiddlewares(MiddlewaresInterface $middlewares): self;
}
