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

use Chevere\Common\Interfaces\ToArrayInterface;
use Chevere\DataStructure\Interfaces\MappedInterface;
use Chevere\Parameter\Interfaces\ParametersInterface;

/**
 * Describes the component in charge of defining the collection of Router dependencies.
 * @extends MappedInterface<ParametersInterface>
 */
interface DependenciesInterface extends ToArrayInterface, MappedInterface
{
    public function get(string $className): ParametersInterface;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function toArray(): array;
}
