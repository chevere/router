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

namespace Chevere\Tests\_resources;

use Chevere\HttpController\HttpController;

final class ControllerNotExportable extends HttpController
{
    private $resource;

    public function run(): array
    {
        return [];
    }

    public function setUpBefore(): void
    {
        $this->resource = fopen('php://output', 'r+');
    }
}
