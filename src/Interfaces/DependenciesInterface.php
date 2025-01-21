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

use Chevere\Parameter\Interfaces\ParametersInterface;

/**
 * Describes the component in charge of defining the collection of Router
 * dependencies which are the parameters required by participants of the
 * request handling.
 *
 * It refers to the parameters indicated at the `__construct` method of the
 * controller and middleware classes used in routing.
 */
interface DependenciesInterface
{
    public function withRoute(RouteInterface ...$route): self;

    /**
     * Provides access to the Parameters instance reflecting the router dependencies.
     */
    public function parameters(): ParametersInterface;

    /**
     * Indicates whether the given class name is a dependency.
     */
    public function has(string $className): bool;

    /**
     * Provides access to the parameters (dependencies) for a known class name.
     */
    public function get(string $className): ParametersInterface;

    /**
     * Provides access to the typed arguments for the given class name and container.
     *
     * @param array<string, mixed> $container Service container
     * @return array<string, mixed> Constructor arguments, taken from the container
     */
    public function extract(string $className, array $container): array;

    public function definedAt(string $variable): string;

    public function assert(mixed ...$argument): void;
}
