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

namespace Chevere\Tests\src;

use Chevere\Http\Controller;
use Chevere\Parameter\Attributes\StringAttr;

final class ControllerWithParameter extends Controller
{
    public function __construct(
        private string $dependency = 'default'
    ) {
    }

    protected function run(
        #[StringAttr('/[0-9]+/')]
        string $id
    ): array {
        return [];
    }
}
