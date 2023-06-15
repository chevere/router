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
 * Describes the component in charge of handling route paths.
 */
interface PathInterface extends Stringable
{
    public function __toString(): string;

    public function variables(): VariablesInterface;

    public function regex(): RegexInterface;

    /**
     * Route without regex variable.
     */
    public function handle(): string;
}
