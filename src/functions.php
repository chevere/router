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
use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\Interfaces\ControllerNameInterface;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Http\MiddlewareName;
use Chevere\Http\Middlewares;
use Chevere\Message\Message;
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Exceptions\VariableNotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Psr\Http\Server\MiddlewareInterface;
use TypeError;
use function Chevere\Action\getParameters;
use function Chevere\Http\middlewares;
use function Chevere\Message\message;

/**
 * Creates Routes object for all `$routes`.
 */
function routes(RouteInterface ...$routes): RoutesInterface
{
    return (new Routes())->withRoute(...$routes);
}

function getPath(string $path, string|BindInterface ...$bind): string
{
    $routePath = new Path($path);
    foreach ($bind as $item) {
        $controllerName = (string) controllerName($item);
        $controllerName::assert();
        foreach ($routePath->variables()->keys() as $variable) {
            $variableBracket = <<<STRING
            {{$variable}}
            STRING;

            try {
                $parameters = getParameters($controllerName);
                $stringParameter = $parameters->required($variable)->string();
            } catch (OutOfBoundsException) {
                throw new VariableNotFoundException(
                    message('Variable %variable% does not exists in controller %controller%')
                        ->withCode('%variable%', $variableBracket)
                        ->withCode('%controller%', $controllerName)
                );
            } catch (TypeError) {
                throw new VariableInvalidException(
                    message('Variable %variable% is not a string parameter in controller %controller%')
                        ->withCode('%variable%', $variableBracket)
                        ->withCode('%controller%', $controllerName)
                );
            }
            $path = str_replace(
                $variableBracket,
                <<<STRING
                {{$variable}:{$stringParameter->regex()->noDelimitersNoAnchors()}}
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
 * @param string $path Route path.
 * @param string $name If not provided it will be same as the route path.
 * @param null|MiddlewaresInterface|class-string<MiddlewareInterface> $middleware HTTP server middlewares.
 * @param BindInterface|string ...$bind Binding for HTTP controllers `GET: bind(HttpController::class, 'view'), POST: ClassName, PUT: ...`.
 */
function route(
    string $path,
    string $name = '',
    null|string|MiddlewaresInterface $middleware = null,
    string|BindInterface ...$bind
): RouteInterface {
    $name = $name === '' ? $path : $name;
    $path = getPath($path, ...$bind);
    $route = new Route(new Path($path), $name);
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
        $middlewares = match (true) {
            is_string($middleware) => middlewares($middleware),
            $middleware === null => middlewares(),
            default => $middleware,
        };
        if ($item instanceof BindInterface) {
            $middlewares = $middlewares->withAppend(
                ...iterator_to_array(
                    $item->middlewares()
                )
            );
        }
        $bind = (new Bind($controllerName, $middlewares))->withView($itemView);
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
 * @param string $controller HTTP controller name
 * @param string $middleware HTTP middleware name
 */
function bind(string $controller, string ...$middleware): BindInterface
{
    $controllerName = new ControllerName($controller);
    $middlewares = new Middlewares();
    foreach ($middleware as $name) {
        $middlewares = $middlewares
            ->withAppend(
                new MiddlewareName($name)
            );
    }

    return new Bind($controllerName, $middlewares);
}

function controllerName(BindInterface|string $item): ControllerNameInterface
{
    if (is_string($item)) {
        $item = bind($item);
    }

    return $item->controllerName();
}
