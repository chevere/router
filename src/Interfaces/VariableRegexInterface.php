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

use Chevere\Regex\Interfaces\RegexInterface;
use Stringable;

/**
 * Describes the component in charge of defining variable regex.
 */
interface VariableRegexInterface extends Stringable
{
    /**
     * Returns the match starting with `^` and ending `$`.
     */
    public function regex(): RegexInterface;
}
