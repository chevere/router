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

use Stringable;

/**
 * Describes the component in charge of defining a variable.
 */
interface VariableInterface extends Stringable
{
    public const ACCEPT_CHARS = '([a-z\_][\w_]*?)';

    public const ACCEPT_CHARS_REGEX = '/^' . self::ACCEPT_CHARS . '+$/i';

    public function regex(): VariableRegexInterface;
}
