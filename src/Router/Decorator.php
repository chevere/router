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

namespace Chevere\Router;

use Chevere\Router\Interfaces\DecoratorInterface;
use Chevere\Router\Interfaces\LocatorInterface;
use Chevere\Router\Interfaces\WildcardsInterface;

final class Decorator implements DecoratorInterface
{
    private WildcardsInterface $wildcards;

    public function __construct(
        private LocatorInterface $locator
    ) {
        $this->wildcards = new Wildcards();
    }

    public function withWildcards(WildcardsInterface $wildcards): DecoratorInterface
    {
        $new = clone $this;
        $new->wildcards = $wildcards;

        return $new;
    }

    public function locator(): LocatorInterface
    {
        return $this->locator;
    }

    public function wildcards(): WildcardsInterface
    {
        return $this->wildcards;
    }
}
