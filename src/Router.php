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

use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\DispatcherInterface;
use Chevere\Router\Interfaces\IndexInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Router\Parsers\StrictStd;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use function Chevere\Message\message;

final class Router implements RouterInterface
{
    private IndexInterface $index;

    private RoutesInterface $routes;

    private RouteCollector $collector;

    private DispatcherInterface $dispatcher;

    private DependenciesInterface $dependencies;

    public function __construct()
    {
        $this->routes = new Routes();
        $this->index = new Index();
        $this->collector = new RouteCollector(new StrictStd(), new GroupCountBased());
        $this->dispatcher = new Dispatcher($this->collector);
        $this->dependencies = new Dependencies();
    }

    public function withAddedRoute(RouteInterface $route, string $group): RouterInterface
    {
        $this->assertHasEndpoints($route);
        $new = clone $this;
        $new->index = $new->index->withAddedRoute($route, $group);
        $new->routes = $new->routes->withRoute($route);
        $new->dependencies = $new->dependencies->withAddedRoute($route);
        foreach ($route->endpoints() as $endpoint) {
            $new->collector->addRoute(
                $endpoint->method()::name(),
                $route->path()->__toString(),
                $endpoint->bind(),
            );
        }

        return $new;
    }

    public function index(): IndexInterface
    {
        return $this->index;
    }

    public function routes(): RoutesInterface
    {
        return $this->routes;
    }

    public function collector(): RouteCollector
    {
        return $this->collector;
    }

    public function dispatcher(): DispatcherInterface
    {
        return $this->dispatcher;
    }

    public function dependencies(): DependenciesInterface
    {
        return $this->dependencies;
    }

    private function assertHasEndpoints(RouteInterface $route): void
    {
        if ($route->endpoints()->count() > 0) {
            return;
        }

        throw new WithoutEndpointsException(
            (string) message(
                "Route `%path%` doesn't contain any endpoint.",
                path: $route->path()->__toString()
            )
        );
    }
}
