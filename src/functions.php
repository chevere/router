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
use Chevere\Parameter\Interfaces\FloatParameterInterface;
use Chevere\Parameter\Interfaces\IntParameterInterface;
use Chevere\Parameter\Interfaces\ParameterInterface;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Regex\Interfaces\RegexInterface;
use Chevere\Regex\Regex;
use Chevere\Router\Exceptions\ControllerNotFoundException;
use Chevere\Router\Exceptions\MiddlewareNotFoundException;
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
                /** @var ParametersInterface $parameters */
                $parameters = $controllerName::reflection()->parameters();
                $parameter = $parameters->get($variable);
            } catch (OutOfBoundsException) {
                throw new VariableNotFoundException(
                    (string) message(
                        'Variable `%variable%` does not exists in controller `%controller%`',
                        variable: $variableBracket,
                        controller: $controllerName,
                    )
                );
            }
            $regex = parameterToRegex($parameter);
            if ($regex === null) {
                throw new VariableInvalidException(
                    (string) message(
                        'Variable `%variable%` is not a `string|int|float` type in controller `%controller%`',
                        variable: $variableBracket,
                        controller: $controllerName,
                    )
                );
            }
            $pattern = $regex->noDelimitersNoAnchors();
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

function parameterToRegex(ParameterInterface $parameter): ?RegexInterface
{
    if ($parameter instanceof StringParameterInterface) {
        return $parameter->regex();
    }
    $pattern = match (true) {
        $parameter instanceof IntParameterInterface => '^\d+$',
        $parameter instanceof FloatParameterInterface => '^\d*\.?\d*$',
        default => null,
    };
    if ($pattern === string()->regex()->noDelimitersNoAnchors()) {
        $pattern = '[^/]+';
    }

    return $pattern !== null
        ? new Regex($pattern)
        : null;
}

/**
 * Creates Route binding HTTP methods to controllers, views, and middleware.
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
 * @param null|MiddlewaresInterface|MiddlewareNameInterface|class-string<MiddlewareInterface> $exclude PSR-15 HTTP Server Middleware to exclude.
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
            if ($item === '') {
                throw new InvalidArgumentException(
                    (string) message(
                        'Binding for `%method%` HTTP method cannot be an empty string for route `%route%`',
                        method: strval($method),
                        route: $name,
                    )
                );
            }

            try {
                $controllerName = controllerName($item);
                $item = match ($view) {
                    '' => headless($item),
                    default => bind($view, $item),
                };
            } catch (ControllerNotFoundException $e) {
                $item = bind($item, NullController::class);

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
 *
 * @throws ControllerNotFoundException
 * @throws MiddlewareNotFoundException
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

        try {
            $middlewares[] = new MiddlewareName($name);
        } catch (Throwable) {
            throw new MiddlewareNotFoundException(
                (string) message(
                    'Middleware `%middleware%` not found for controller `%controller%`',
                    middleware: $name,
                    controller: $controller
                )
            );
        }
    }

    try {
        $controllerName = new ControllerName($controller);
    } catch (Throwable) {
        throw new ControllerNotFoundException(
            (string) message(
                'Controller `%controller%` not found',
                controller: $controller
            )
        );
    }

    return new Bind(
        $controllerName,
        new Middlewares(...$middlewares),
        view: $view
    );
}

/**
 * Headless binds a Controller to middleware.
 *
 * @param string $controller HTTP controller name
 * @param string|MiddlewareNameInterface ...$middleware HTTP middleware name(s)
 *
 * @throws MiddlewareNotFoundException
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

        try {
            $middlewares[] = new MiddlewareName($value);
        } catch (Throwable) {
            throw new MiddlewareNotFoundException(
                (string) message(
                    'Middleware `%middleware%` not found for controller `%controller%`',
                    middleware: $value,
                    controller: $controller
                )
            );
        }
    }

    return new Bind(
        new ControllerName($controller),
        new Middlewares(...$middlewares),
        view: ''
    );
}

/**
 * @throws ControllerNotFoundException
 */
function controllerName(BindInterface|string $item): ControllerNameInterface
{
    if (is_string($item)) {
        try {
            return new ControllerName($item);
        } catch (Throwable) {
            throw new ControllerNotFoundException(
                (string) message(
                    'Controller `%controller%` not found',
                    controller: $item
                )
            );
        }
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
