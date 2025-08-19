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

use Chevere\Router\Container;
use Closure;
use FastRoute\RouteCollector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Describes the component in charge of handling router.
 */
interface RouterInterface
{
    /**
     * Return an instance with the specified added `$route`.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified added `$route`.
     */
    public function withRoute(RouteInterface $route, string $group): self;

    public function index(): IndexInterface;

    public function routes(): RoutesInterface;

    public function collector(): RouteCollector;

    public function dependencies(): DependenciesInterface;

    public function views(): ViewsInterface;

    /**
     * Gets a new RoutedInterface instance for the given server request.
     */
    public function getRouted(
        ServerRequestInterface $serverRequest,
        ResponseFactoryInterface $responseFactory = new Psr17Factory(),
        ContainerInterface $container = new Container(),
        ?Closure $callback = null
    ): RoutedInterface;
}
