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

use Chevere\Attribute\StringRegex;
use Chevere\Http\Controller;

final class ControllerWithParameter extends Controller
{
    public function __construct(
        private string $dependency = 'default'
    ) {
    }

    public function run(
        #[StringRegex('/[0-9]+/')]
        string $id
    ): array {
        return [];
    }
}
