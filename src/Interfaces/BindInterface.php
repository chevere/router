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

use Chevere\HttpController\Interfaces\HttpControllerInterface;

/**
 * Describes the component in charge of binding a HttpControllerInterface
 * to a view.
 */
interface BindInterface
{
    public function controllerName(): string;

    public function view(): string;
}
