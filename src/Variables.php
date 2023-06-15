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

use Chevere\DataStructure\Traits\MapToArrayTrait;
use Chevere\DataStructure\Traits\MapTrait;
use Chevere\Router\Interfaces\VariableInterface;
use Chevere\Router\Interfaces\VariablesInterface;

final class Variables implements VariablesInterface
{
    /**
     * @template-use MapTrait<VariableInterface>
     */
    use MapTrait;

    use MapToArrayTrait;

    public function withPut(VariableInterface $variable): VariablesInterface
    {
        $new = clone $this;
        $new->map = $new->map
            ->withPut($variable->__toString(), $variable);

        return $new;
    }

    public function has(string $name): bool
    {
        return $this->map->has($name);
    }

    public function get(string $name): VariableInterface
    {
        /** @var VariableInterface */
        return $this->map->get($name);
    }
}
