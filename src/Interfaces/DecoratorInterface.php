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

/**
 * Describes the component in charge of decorate a route.
 */
interface DecoratorInterface
{
    public function locator(): LocatorInterface;

    /**
     * Return an instance with the specified `variables` instance.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `variables` instance.
     */
    public function withVariables(VariablesInterface $variables): self;

    public function variables(): VariablesInterface;
}
