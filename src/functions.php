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
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\HttpController\HttpControllerName;
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
 * @param MiddlewaresInterface $middleware HTTP server middlewares.
 * @param BindInterface|string ...$bind Binding for HTTP controllers `GET: bind(HttpController::class, 'view'), POST: ClassName, PUT: ...`.
 */
function route(
    string $path,
    string $name = '',
    MiddlewaresInterface $middleware = null,
    BindInterface|string ...$bind
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
        $route = $route->withEndpoint(
            new Endpoint(
                $method,
                new Bind($controllerName, $itemView)
            )
        );
        if ($middleware !== null) {
            $route = $route->withMiddlewares(
                $route->middlewares()->withPrepend(
                    ...iterator_to_array(
                        $middleware->getIterator()
                    )
                )
            );
        }
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
        $group = ! is_numeric($group)
            ? strval($group)
            : '';
        foreach ($items as $route) {
            $router = $router->withAddedRoute($route, $group);
        }
    }

    return $router;
}

/**
 * @param string $name HTTP controller name
 */
function bind(
    string $name,
    string $view = ''
): BindInterface {
    return new Bind(
        new HttpControllerName($name),
        $view
    );
}

function controllerName(BindInterface|string $item): HttpControllerNameInterface
{
    if (is_string($item)) {
        $item = bind($item);
    }

    return $item->controllerName();
}
