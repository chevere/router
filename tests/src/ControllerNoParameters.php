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

use Chevere\Http\Attributes\Response;
use Chevere\Http\Controller;
use Chevere\Http\Header;
use Chevere\Http\Status;

#[Response(
    new Status(200),
    new Header('Content-Type', 'text/html')
)]
final class ControllerNoParameters extends Controller
{
    protected function main(): array
    {
        return [];
    }
}
