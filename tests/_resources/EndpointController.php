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

use Chevere\Attributes\Regex;
use Chevere\Http\Controller;

final class EndpointController extends Controller
{
    public function run(
        #[Regex('/[\w]+/')]
        string $name,
        #[Regex('/[0-9]+/')]
        string $id
    ): array {
        return [];
    }
}
