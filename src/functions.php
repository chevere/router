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
use Chevere\HttpController\Interfaces\HttpControllerInterface;
use function Chevere\Message\message;
use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Exceptions\WildcardNotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;

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
 * @param MiddlewaresInterface $middleware PSR HTTP server middlewares.
 * @param BindInterface|HttpControllerInterface ...$bind Binding for HTTP controllers `GET: bind(HttpController, 'view'), POST:...`.
 */
function route(
    string $path,
    string $name = '',
    ?MiddlewaresInterface $middleware = null,
    BindInterface|HttpControllerInterface ...$bind
): RouteInterface {
    $name = $name === '' ? $path : $name;
    $routePath = new Path($path);
    $route = (new Route(new Path($path), $name));
    foreach ($bind as $item) {
        $controller = $item instanceof BindInterface
            ? $item->controller()
            : $item;
        foreach ($routePath->wildcards()->keys() as $wildcard) {
            $wildcardBracket = <<<STRING
            {{$wildcard}}
            STRING;

            try {
                /** @var StringParameterInterface $controllerParameter */
                $controllerParameter = $controller->parameters()->get($wildcard);
            } catch (OutOfBoundsException) {
                throw new WildcardNotFoundException(
                    message('Wildcard %wildcard% does not exists in controller %controller%')
                        ->withCode('%wildcard%', $wildcardBracket)
                        ->withCode('%controller%', $controller::class)
                );
            }
            $path = str_replace(
                $wildcardBracket,
                <<<STRING
                {{$wildcard}:{$controllerParameter->regex()->noDelimitersNoAnchors()}}
                STRING,
                $path
            );
        }
    }
    $route = (new Route(new Path($path), $name));
    foreach ($bind as $method => $item) {
        $isBind = $item instanceof BindInterface;
        $controller = $isBind
            ? $item->controller()
            : $item;
        $httpMethod = strval($method);
        $method = EndpointInterface::KNOWN_METHODS[$method] ?? null;
        if ($method === null) {
            throw new MethodNotAllowedException(
                message: (new Message('Unknown HTTP method `%provided%` provided for %controller% controller.'))
                    ->withCode('%provided%', $httpMethod)
                    ->withCode('%controller%', $controller::class)
            );
        }
        if ($middleware !== null) {
            $controller = $controller->withMiddlewares(
                $controller->middlewares()->withPrepend(
                    ...iterator_to_array(
                        $middleware->getIterator()
                    )
                )
            );
        }
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
                bind($controller, $itemView)
            )
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
 * Binds an HttpControllerInterface to a view.
 */
function bind(
    HttpControllerInterface $controller,
    string $view = ''
): BindInterface {
    return new Bind($controller, $view);
}
