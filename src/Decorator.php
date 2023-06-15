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
use Chevere\Router\Interfaces\VariablesInterface;

final class Decorator implements DecoratorInterface
{
    private VariablesInterface $variables;

    public function __construct(
        private LocatorInterface $locator
    ) {
        $this->variables = new Variables();
    }

    public function withVariables(VariablesInterface $variables): DecoratorInterface
    {
        $new = clone $this;
        $new->variables = $variables;

        return $new;
    }

    public function locator(): LocatorInterface
    {
        return $this->locator;
    }

    public function variables(): VariablesInterface
    {
        return $this->variables;
    }
}
