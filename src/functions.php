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

use Chevere\Controller\Interfaces\HttpControllerInterface;
use Chevere\Controller\Interfaces\HttpMiddlewareInterface;
use Chevere\Http\Exceptions\HttpMethodNotAllowedException;
use Chevere\Http\Interfaces\MethodInterface;
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
 * @param string $view View namespace.
 * @param HttpMiddlewareInterface $middleware Route level middleware (top priority).
 * @param BindInterface|HttpControllerInterface ...$bind Binding for HTTP controllers `GET: bind(HttpController, 'view'), POST:...`.
 */
function route(
    string $path,
    string $name = '',
    string $view = '',
    ?HttpMiddlewareInterface $middleware = null,
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
        $controller = $item instanceof BindInterface
            ? $item->controller()
            : $item;
        $httpMethod = strval($method);
        $method = EndpointInterface::KNOWN_METHODS[$method] ?? null;
        if ($method === null) {
            throw new HttpMethodNotAllowedException(
                message: (new Message('Unknown HTTP method `%provided%` provided for %controller% controller.'))
                    ->withCode('%provided%', $httpMethod)
                    ->withCode('%controller%', $controller::class)
            );
        }
        if ($middleware !== null) {
            $controller = $controller->withMiddleware(
                $controller->middleware()->withPrepend(
                    ...iterator_to_array(
                        $middleware->getIterator()
                    )
                )
            );
        }
        $itemView = $item instanceof BindInterface
            ? $item->view()
            : '';
        $itemView = match (true) {
            $view !== '' && $itemView !== '' => "{$view}/{$itemView}/{$httpMethod}",
            $view !== '' => "{$view}/{$httpMethod}",
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
    foreach ($routes as $group => $groupRoutes) {
        $group = strval($group);
        foreach ($groupRoutes->getIterator() as $route) {
            $router = $router
                ->withAddedRoute($group, $route);
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
