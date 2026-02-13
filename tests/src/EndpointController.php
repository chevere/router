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
use Chevere\Parameter\Attributes\_string;

final class EndpointController extends Controller
{
    public function __invoke(
        #[_string('/[\w]+/')]
        string $name,
        #[_string('/[0-9]+/')]
        string $id
    ): array {
        return [];
    }
}
