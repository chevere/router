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

use Chevere\DataStructure\Interfaces\StringMappedInterface;
use Iterator;

/**
 * Describes the component in charge of collecting objects implementing `VariableInterface`.
 *
 * @extends StringMappedInterface<VariableInterface>
 */
interface VariablesInterface extends StringMappedInterface
{
    /**
     * Return an instance with the specified `$variable`.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified `$variable`.
     *
     * This method should overrides existing variables.
     */
    public function withPut(VariableInterface $variable): self;

    /**
     * Returns a boolean indicating whether the instance has a given VariableInterface.
     */
    public function has(string $name): bool;

    /**
     * Provides access to the target VariableInterface instance.
     */
    public function get(string $name): VariableInterface;

    /**
     * @return Iterator<string , VariableInterface>
     */
    public function getIterator(): Iterator;

    /**
     * Provides access to an array representation of the instance.
     *
     * @return array<string, VariableInterface>
     */
    public function toArray(): array;
}
