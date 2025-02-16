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

namespace Chevere\Router\Interfaces;

use InvalidArgumentException;
use LogicException;

/**
 * Describes the component in charge of asserting routed views.
 */
interface ViewsInterface
{
    /**
     * Asserts the registered router views in the given `$dir`.
     *
     * @throws InvalidArgumentException If `$dir` does not exists.
     * @throws LogicException For all view files not found.
     */
    public function assert(string $dir): void;
}
