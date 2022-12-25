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

use Chevere\Controller\Interfaces\HttpControllerInterface;
use Chevere\Router\Interfaces\BindInterface;

final class Bind implements BindInterface
{
    public function __construct(
        private HttpControllerInterface $httpController,
        private string $view
    ) {
    }

    public function controller(): HttpControllerInterface
    {
        return $this->httpController;
    }

    public function view(): string
    {
        return $this->view;
    }
}
