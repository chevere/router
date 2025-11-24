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

use Chevere\Http\ControllerName;
use Chevere\Http\Controllers\NullController;
use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\Interfaces\ControllerInterface;
use Chevere\Http\Interfaces\ControllerNameInterface;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewareNameInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Http\MiddlewareName;
use Chevere\Http\Middlewares;
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Exceptions\VariableNotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use InvalidArgumentException;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Throwable;
use TypeError;
use function Chevere\Http\middlewares;
use function Chevere\Message\message;
use function Chevere\Parameter\string;

/**
 * Creates Routes object for all `$routes`.
 */
function routes(RouteInterface|RoutesInterface ...$routes): RoutesInterface
{
    $object = new Routes();
    foreach ($routes as $item) {
        if ($item instanceof RoutesInterface) {
            $object = $object->withRoutes($item);

            continue;
        }
        $object = $object->withRoute($item);
    }

    return $object;
}

function getPath(string $path, string|BindInterface ...$bind): string
{
    $defaultStringRegex = string()->regex()->noDelimitersNoAnchors();
    $routePath = new Path($path);
    foreach ($bind as $item) {
        try {
            $controllerName = controllerName($item)->__toString();
        } catch (Throwable) {
            continue;
        }
        $controllerName::reflection();
        foreach ($routePath->variables()->keys() as $variable) {
            $variableBracket = <<<STRING
            {{$variable}}
            STRING;

            try {
                $parameters = $controllerName::reflection()->parameters();
                $stringParameter = $parameters->required($variable)->string();
            } catch (OutOfBoundsException) {
                throw new VariableNotFoundException(
                    (string) message(
                        'Variable `%variable%` does not exists in controller `%controller%`',
                        variable: $variableBracket,
                        controller: $controllerName,
                    )
                );
            } catch (TypeError) {
                throw new VariableInvalidException(
                    (string) message(
                        'Variable `%variable%` is not a string parameter in controller `%controller%`',
                        variable: $variableBracket,
                        controller: $controllerName,
                    )
                );
            }
            $pattern = $stringParameter->regex()->noDelimitersNoAnchors();
            if ($pattern === $defaultStringRegex) {
                $pattern = '[^/]+';
            }
            $path = str_replace(
                $variableBracket,
                <<<STRING
                {{$variable}:{$pattern}}
                STRING,
                $path
            );
        }
    }

    return $path;
}

/**
 * Creates Route binding.
 *
 * `$bind` examples:
 *
 * ```php
 * GET: MyController::class,
 * POST: 'my-view.twig',
 * PATCH: bind(MyController::class, 'my-view.twig', MyMiddleware::class),
 * ```
 *
 * @param string $path Route path like `/my-route/{id}`.
 * @param string $name Route name, if not provided will be same as `$path`.
 * @param null|MiddlewaresInterface|MiddlewareNameInterface|class-string<MiddlewareInterface> $middleware PSR-15 HTTP Server Middleware.
 * @param BindInterface|class-string<ControllerInterface>|string ...$bind Binding for HTTP methods to a handler (controller, view, middleware).
 */
function route(
    string $path,
    string $name = '',
    string $view = '',
    null|string|MiddlewaresInterface|MiddlewareNameInterface $middleware = null,
    null|string|MiddlewaresInterface|MiddlewareNameInterface $exclude = null,
    string|BindInterface ...$bind
): RouteInterface {
    $name = $name === '' ? $path : $name;
    $path = getPath($path, ...$bind);
    $excludes = match (true) {
        $exclude instanceof MiddlewaresInterface => $exclude,
        $exclude === null => middlewares(),
        default => middlewares($exclude),
    };
    $route = new Route(new Path($path), $name, $excludes);
    foreach ($bind as $method => $item) {
        if ($item instanceof BindInterface) {
            $controllerName = $item->controllerName();
        } else {
            try {
                $controllerName = controllerName($item);
                if ($view === '') {
                    $item = headless($item);
                } else {
                    $item = bind($view, $item);
                }
            } catch (Throwable $e) {
                if ($item === '') {
                    $item = headless(NullController::class);
                } else {
                    throw $e;
                }
                $controllerName = $item->controllerName();
            }
        }
        $httpMethod = strval($method);
        $method = EndpointInterface::KNOWN_METHODS[$method] ?? null;
        if ($method === null) {
            throw new MethodNotAllowedException(
                (string) message(
                    'Unknown HTTP method `%provided%` provided for `%controller%` controller.',
                    provided: $httpMethod,
                    controller: $controllerName->__toString(),
                ),
                405
            );
        }
        /** @var MethodInterface $object */
        $object = new $method(); // @phpstan-ignore-line
        $middlewares = match (true) {
            $middleware instanceof MiddlewaresInterface => $middleware,
            $middleware === null => middlewares(),
            default => middlewares($middleware),
        };
        $middlewares = $middlewares->withAppend(
            ...iterator_to_array(
                $item->middlewares()
            )
        );
        $bind = (new Bind($controllerName, $middlewares))->withView($item->view());
        $route = $route->withEndpoint(
            new Endpoint($object, $bind)
        );
    }

    return $route;
}

/**
 * Creates a Router for named Routes groups.
 */
function router(RoutesInterface ...$routes): RouterInterface
{
    $router = new Router();
    foreach ($routes as $group => $items) {
        $group = match (true) {
            is_numeric($group) => '',
            default => strval($group)
        };
        foreach ($items as $route) {
            $router = $router->withRoute($route, $group);
        }
    }

    return $router;
}

/**
 * Binds a view to a controller and middleware.
 *
 * @param string $view View name
 * @param string $controller HTTP controller name
 * @param string ...$middleware HTTP middleware name(s)
 */
function bind(
    string $view,
    string $controller = NullController::class,
    string|MiddlewareNameInterface ...$middleware
): BindInterface {
    if ($view === '') {
        throw new InvalidArgumentException(
            (string) message(
                'Argument `view` provided is empty for controller `%controller%`',
                controller: $controller
            )
        );
    }
    $middlewares = [];
    foreach ($middleware as $name) {
        if ($name instanceof MiddlewareNameInterface) {
            $middlewares[] = $name;

            continue;
        }
        $middlewares[] = new MiddlewareName($name);
    }

    return new Bind(
        new ControllerName($controller),
        new Middlewares(...$middlewares),
        view: $view
    );
}

/**
 * Headless binds a Controller to middleware.
 *
 * @param string $controller HTTP controller name
 * @param string|MiddlewareNameInterface ...$middleware HTTP middleware name(s)
 */
function headless(
    string $controller = NullController::class,
    string|MiddlewareNameInterface ...$middleware
): BindInterface {
    $middlewares = [];
    foreach ($middleware as $value) {
        if (is_object($value)) {
            $middlewares[] = $value;

            continue;
        }
        $middlewares[] = new MiddlewareName($value);
    }

    return new Bind(
        new ControllerName($controller),
        new Middlewares(...$middlewares),
        view: ''
    );
}

function controllerName(BindInterface|string $item): ControllerNameInterface
{
    if (is_string($item)) {
        return new ControllerName($item);
    }

    return $item->controllerName();
}

/**
 * Merges response headers from controller and response.
 *
 * @param array<string, string> $controllerHeaders
 * @param array<string, string> $responseHeaders
 */
function mergeResponseHeaders(
    ResponseInterface &$response,
    array $controllerHeaders,
    array $responseHeaders
): void {
    $headers = array_merge($controllerHeaders, $responseHeaders);
    foreach ($headers as $name => $value) {
        $response = $response->withHeader($name, $value);
    }
}
