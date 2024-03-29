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

use FastRoute\RouteCollector;

/**
 * Describes the component in charge of handling routing.
 */
interface RouterInterface
{
    /**
     * Return an instance with the specified added `$route`.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified added `$route`.
     */
    public function withAddedRoute(RouteInterface $route, string $group): self;

    /**
     * Provides access to the index instance.
     */
    public function index(): IndexInterface;

    /**
     * Provides access to the routes instance.
     */
    public function routes(): RoutesInterface;

    /**
     * Provides access to the route collector instance.
     */
    public function routeCollector(): RouteCollector;
}
