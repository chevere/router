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

use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewareInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use function Chevere\Http\middlewares;
use Chevere\HttpController\HttpControllerName;
use Chevere\HttpController\Interfaces\HttpControllerInterface;
use Chevere\HttpController\Interfaces\HttpControllerNameInterface;
use function Chevere\Message\message;
use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Router\Exceptions\WildcardInvalidException;
use Chevere\Router\Exceptions\WildcardNotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use TypeError;

/**
 * Creates Routes object for all `$routes`.
 */
function routes(RouteInterface ...$routes): RoutesInterface
{
    return (new Routes())->withAdded(...$routes);
}

/**
 * Creates Route binding.
 *
 * @param string $path Route path.
 * @param string $name If not provided it will be same as the route path.
 * @param MiddlewaresInterface|class-string<MiddlewareInterface> $middleware HTTP server middlewares.
 * @param BindInterface|string ...$bind Binding for HTTP controllers `GET: bind(HttpController::class, 'view'), POST: ClassName, PUT: ...`.
 */
function route(
    string $path,
    string $name = '',
    string|MiddlewaresInterface $middleware = null,
    string|BindInterface ...$bind
): RouteInterface {
    $name = $name === '' ? $path : $name;
    $routePath = new Path($path);
    $route = (new Route(new Path($path), $name));
    foreach ($bind as $item) {
        $controllerName = controllerName($item);
        foreach ($routePath->wildcards()->keys() as $wildcard) {
            $wildcardBracket = <<<STRING
            {{$wildcard}}
            STRING;

            try {
                /** @var ParametersInterface $parameters */
                $parameters = $controllerName->__toString()::getParameters();
                $stringParameter = $parameters->getString($wildcard);
            } catch (OutOfBoundsException) {
                throw new WildcardNotFoundException(
                    message('Wildcard %wildcard% does not exists in controller %controller%')
                        ->withCode('%wildcard%', $wildcardBracket)
                        ->withCode('%controller%', $controllerName->__toString())
                );
            } catch(TypeError) {
                throw new WildcardInvalidException(
                    message('Wildcard %wildcard% is not a string parameter in controller %controller%')
                        ->withCode('%wildcard%', $wildcardBracket)
                        ->withCode('%controller%', $controllerName->__toString())
                );
            }
            $path = str_replace(
                $wildcardBracket,
                <<<STRING
                {{$wildcard}:{$stringParameter->regex()->noDelimitersNoAnchors()}}
                STRING,
                $path
            );
        }
    }
    $route = (new Route(new Path($path), $name));
    foreach ($bind as $method => $item) {
        $controllerName = controllerName($item);
        $httpMethod = strval($method);
        $method = EndpointInterface::KNOWN_METHODS[$method] ?? null;
        if ($method === null) {
            throw new MethodNotAllowedException(
                message: (new Message('Unknown HTTP method `%provided%` provided for %controller% controller.'))
                    ->withCode('%provided%', $httpMethod)
                    ->withCode('%controller%', $controllerName->__toString())
            );
        }
        $isBind = $item instanceof BindInterface;
        $itemView = $isBind
            ? $item->view()
            : '';
        $itemView = match (true) {
            $itemView === '' && $isBind => $httpMethod,
            $itemView !== '' => "{$itemView}/{$httpMethod}",
            default => '',
        };
        /** @var MethodInterface $method */
        $method = new $method();
        $middleware = match (true) {
            is_string($middleware) => middlewares($middleware),
            $middleware === null => middlewares(),
            default => $middleware,
        };
        if ($item instanceof BindInterface) {
            $middleware = $middleware->withAppend(
                ...iterator_to_array(
                    $item->middlewares()
                )
            );
        }
        $bind = bind($controllerName->__toString(), $middleware, $itemView);
        $endpoint = new Endpoint($method, $bind);
        $route = $route->withEndpoint($endpoint);
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
            $router = $router->withAddedRoute($route, $group);
        }
    }

    return $router;
}

/**
 * @param string $controller HttpControllerInterface HTTP controller name
 */
function bind(
    string $controller,
    string|MiddlewaresInterface $middlewares = null,
    string $view = '',
): BindInterface {
    $controller = new HttpControllerName($controller);
    $middlewares = match (true) {
        is_string($middlewares) => middlewares($middlewares),
        $middlewares === null => middlewares(),
        default => $middlewares,
    };

    return new Bind($controller, $middlewares, $view);
}

function controllerName(BindInterface|string $item): HttpControllerNameInterface
{
    if (is_string($item)) {
        $item = bind($item);
    }

    return $item->controllerName();
}
