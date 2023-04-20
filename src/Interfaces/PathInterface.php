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
    /**
     * Route used to construct the instance.
     */
    public function __toString(): string;

    /**
     * Provides access to the wildcards instance.
     */
    public function wildcards(): WildcardsInterface;

    /**
     * Provides access to the regex instance.
     */
    public function regex(): RegexInterface;

    /**
     * Route without regex wildcards.
     */
    public function handle(): string;
}
