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
use LogicException;

final class ControllerSetUpThrows extends Controller
{
    public function setUp(): void
    {
        throw new LogicException('setUp');
    }

    public function __invoke(): array
    {
        return [];
    }
}
