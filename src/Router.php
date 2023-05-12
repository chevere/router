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

use Chevere\Message\Message;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Interfaces\IndexInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Router\Parsers\StrictStd;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;

final class Router implements RouterInterface
{
    private IndexInterface $index;

    private RoutesInterface $routes;

    private RouteCollector $routeCollector;

    public function __construct()
    {
        $this->routes = new Routes();
        $this->index = new Index();
        $this->routeCollector = new RouteCollector(new StrictStd(), new GroupCountBased());
    }

    public function withAddedRoute(RouteInterface $route, string $group): RouterInterface
    {
        $this->assertHasEndpoints($route);
        $new = clone $this;
        $new->index = $new->index->withAddedRoute($route, $group);
        $new->routes = $new->routes->withRoute($route);
        foreach ($route->endpoints() as $endpoint) {
            $new->routeCollector->addRoute(
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

    public function routeCollector(): RouteCollector
    {
        return $this->routeCollector;
    }

    private function assertHasEndpoints(RouteInterface $route): void
    {
        if ($route->endpoints()->count() > 0) {
            return;
        }

        throw new WithoutEndpointsException(
            (new Message("Route %path% doesn't contain any endpoint."))
                ->withCode('%path%', $route->path()->__toString())
        );
    }
}
