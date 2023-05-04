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

use Chevere\HttpController\HttpControllerName;
use Chevere\Router\Interfaces\BindInterface;

final class Bind implements BindInterface
{
    public function __construct(
        private HttpControllerName $httpController,
        private string $view = ''
    ) {
    }

    public function controllerName(): string
    {
        return $this->httpController->__toString();
    }

    public function view(): string
    {
        return $this->view;
    }
}
