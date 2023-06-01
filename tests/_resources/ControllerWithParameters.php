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

use Chevere\Attribute\StringAttribute;
use Chevere\Http\HttpController;

final class ControllerWithParameters extends HttpController
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
