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

use Chevere\Http\Exceptions\ControllerException;
use Chevere\Http\Interfaces\ControllerInterface;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Interfaces\ContainerInterface;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\DispatcherInterface;
use Chevere\Router\Interfaces\IndexInterface;
use Chevere\Router\Interfaces\RoutedInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Router\Interfaces\ViewsInterface;
use Chevere\Router\Parsers\StrictStd;
use Closure;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;
use Relay\Relay;
use Throwable;
use function Chevere\Http\requestAttribute;
use function Chevere\Http\responseAttribute;
use function Chevere\Message\message;

final class Router implements RouterInterface
{
    private IndexInterface $index;

    private RoutesInterface $routes;

    private RouteCollector $collector;

    private DispatcherInterface $dispatcher;

    private DependenciesInterface $dependencies;

    private ViewsInterface $views;

    public function __construct()
    {
        $this->routes = new Routes();
        $this->index = new Index();
        $this->collector = new RouteCollector(new StrictStd(), new GroupCountBased());
        $this->dispatcher = new Dispatcher($this->collector);
        $this->dependencies = new Dependencies();
        $this->views = new Views($this->routes);
    }

    public function withRoute(RouteInterface $route, string $group): RouterInterface
    {
        $this->assertHasEndpoints($route);
        $new = clone $this;
        $new->index = $new->index->withRoute($route, $group);
        $new->routes = $new->routes->withRoute($route);
        $new->dependencies = $new->dependencies->withRoute($route);
        foreach ($route->endpoints() as $endpoint) {
            $new->collector->addRoute(
                $endpoint->method()::name(),
                $route->path()->__toString(),
                $endpoint->bind(),
            );
        }
        $new->views = new Views($new->routes);

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

    public function dependencies(): DependenciesInterface
    {
        return $this->dependencies;
    }

    public function views(): ViewsInterface
    {
        return $this->views;
    }

    public function getRouted(
        ServerRequestInterface $serverRequest,
        ResponseFactoryInterface $responseFactory = new Psr17Factory(),
        ContainerInterface $container = new Container(),
        ?Closure $callback = null
    ): RoutedInterface {
        $container = $container->with(responseFactory: $responseFactory);
        $routed = $this->dispatcher->dispatch($serverRequest);
        $queue = [];
        $middlewares = $routed->bind()->middlewares();
        foreach ($middlewares as $middlewareName) {
            $className = (string) $middlewareName;
            $middlewareDependencies = $this->dependencies->extract($className, $container);
            $middleware = new $className(...$middlewareDependencies);
            if (method_exists($middleware, 'setUp')) {
                $reflection = new ReflectionMethod($middleware, 'setUp');
                $parameters = $reflection->getParameters();
                $lastParameter = end($parameters);
                if ($lastParameter && $lastParameter->isVariadic()) {
                    $arguments = $middlewareName->arguments();
                    $variadic = array_pop($arguments);
                    if (! is_iterable($variadic)) {
                        $variadic = [$variadic];
                    }
                    $middleware->setUp(...$arguments, ...$variadic);
                } else {
                    $middleware->setUp(...$middlewareName->arguments());
                }
            }
            $queue[$className] = $middleware;
        }
        $handle = new RelayHandle($responseFactory, $serverRequest);
        $queue[] = $handle;
        $relay = new Relay($queue);
        $response = $relay->handle($serverRequest);
        if ($response->getStatusCode() !== 0) {
            return new Routed($response, $routed->bind());
        }
        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $responseHeaders[$name] = implode(', ', $values);
        }
        if ($callback) {
            $container = $callback($container);
        }
        $controllerName = $routed->bind()->controllerName();
        $controllerNameString = $controllerName->__toString();
        $requestAttribute = requestAttribute($controllerNameString);
        $responseAttribute = responseAttribute($controllerNameString);
        $controllerStatus = $responseAttribute?->status->success
            ?? 200;
        $controllerRequestHeaders = $requestAttribute?->headers->toArray()
            ?? [];
        $controllerResponseHeaders = $responseAttribute?->headers->toArray()
            ?? [];
        foreach ($controllerResponseHeaders as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        if ($response->hasHeader('Location')) {
            return new Routed($response, $routed->bind());
        }
        $request = $handle->request();
        foreach ($controllerRequestHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $container = $container->with(request: $request);
        $controllerArguments = $this->dependencies->extract($controllerNameString, $container);
        /** @var ControllerInterface $controller */
        $controller = new $controllerNameString(...$controllerArguments);

        try {
            $controller = $controller->withServerRequest($request);
            if (method_exists($controller, 'setUp')) {
                $controller->setUp(
                    ...$controllerName->arguments()
                );
            }
        } catch (Throwable $e) {
            return (new Routed(
                $responseFactory->createResponse(400),
                $routed->bind(),
            ))->withThrowable($e);
        }

        try {
            $controllerReturn = $controller->__invoke(...$routed->arguments());
        } catch (Throwable $e) {
            $code = $e instanceof ControllerException
                ? (int) $e->getCode()
                : 500;
            $response = $responseFactory->createResponse($code);
            mergeResponseHeaders($response, $controllerResponseHeaders, $responseHeaders);

            return (new Routed($response, $routed->bind()))
                ->withThrowable($e);
        }

        $response = $responseFactory->createResponse($controllerStatus);
        mergeResponseHeaders($response, $controllerResponseHeaders, $responseHeaders);

        return new Routed(
            $controller->terminate($response),
            $routed->bind(),
            $controllerReturn
        );
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
