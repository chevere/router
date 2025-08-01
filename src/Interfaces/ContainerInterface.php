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
use Chevere\Parameter\Interfaces\ParametersAccessInterface;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Psr\Container\ContainerInterface as Psr11ContainerInterface;

/**
 * Describes the component in charge of providing a dependency injection container.
 *
 * @extends StringMappedInterface<mixed>
 */
interface ContainerInterface extends Psr11ContainerInterface, StringMappedInterface
{
    /**
     * Return an instance with the specified named entries included.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified named entries.
     */
    public function with(mixed ...$entry): self;

    /**
     * Return an instance with the specified named entries removed.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that doesn't contains the specified named entries.
     */
    public function without(string ...$entry): self;

    /**
     * Return an instance with auto injected dependencies.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains auto injected dependencies.
     */
    public function withAutoInject(
        ParametersInterface|ParametersAccessInterface $dependencies,
        string ...$ignore
    ): self;

    /**
     * Provides access to the container dependencies for a given class name.
     *
     * @return array<string, mixed> Constructor arguments found in the container
     */
    public function extract(string $className): array;
}
