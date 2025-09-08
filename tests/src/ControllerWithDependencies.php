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
use Chevere\Router\Path;

final class ControllerWithDependencies extends Controller
{
    public function __construct(
        private int $int,
        private Path $dependency = new Path('/path'),
    ) {
    }

    public function __invoke(
        #[StringAttr('/[0-9]+/')]
        string $id
    ): array {
        return [];
    }
}
