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

namespace Chevere\Router\Tests\_resources;

use Chevere\Attribute\StringAttribute;
use Chevere\Controller\HttpController;

final class TestControllerWithParameters extends HttpController
{
    public function run(
        #[StringAttribute('/\w+/')]
        string $name,
        #[StringAttribute('/\d+/')]
        string $id
    ): array {
        return [];
    }
}
