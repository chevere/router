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
use Chevere\Filesystem\Exceptions\FileInvalidContentsException;
use Chevere\Filesystem\Exceptions\FileNotExistsException;
use Chevere\Filesystem\Exceptions\FileReturnInvalidTypeException;
use Chevere\Filesystem\Exceptions\FilesystemException;
use Chevere\Filesystem\Exceptions\FileUnableToGetException;
use Chevere\Filesystem\Exceptions\FileWithoutContentsException;
use function Chevere\Filesystem\filePhpReturnForPath;
use Chevere\Http\Exceptions\HttpMethodNotAllowedException;
use Chevere\Http\Interfaces\MethodInterface;
use function Chevere\Message\message;
use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Exceptions\NotRoutableException;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use Chevere\Throwable\Exceptions\RuntimeException;
use Chevere\Type\Type;

function routes(RouteInterface ...$namedRoutes): RoutesInterface
{
    return (new Routes())->withAdded(...$namedRoutes);
}

/**
 * @param string $path Route path.
 * @param string $name If not provided it will be same as the route path.
 * @param string $view View binding.
 * @param HttpMiddlewareInterface $middleware Route level middleware (top priority).
 */
function route(
    string $path,
    string $name = '',
    string $view = '',
    ?HttpMiddlewareInterface $middleware = null,
    HttpControllerInterface ...$httpControllers
): RouteInterface {
    $name = $name === '' ? $path : $name;
    $routePath = new Path($path);
    $route = (new Route(new Path($path), $name, $view));
    foreach ($httpControllers as $httpController) {
        foreach ($routePath->wildcards()->keys() as $wildcard) {
            $wildcardBracket = '{' . $wildcard . '}';

            try {
                /** @var StringParameterInterface $controllerParameter */
                $controllerParameter = $httpController->parameters()->get($wildcard);
            } catch (OutOfBoundsException) {
                throw new InvalidArgumentException(
                    message('Wildcard %wildcard% does not exists in controller %controller%')
                        ->withCode('%wildcard%', $wildcardBracket)
                        ->withCode('%controller%', $httpController::class)
                );
            }
            $path = str_replace(
                $wildcardBracket,
                '{' . $wildcard . ':'
                    . $controllerParameter->regex()->noDelimitersNoAnchors()
                    . '}',
                $path
            );
        }
    }
    $route = (new Route(new Path($path), $name, $view));
    foreach ($httpControllers as $httpMethod => $httpController) {
        $httpMethod = strval($httpMethod);
        $method = EndpointInterface::KNOWN_METHODS[$httpMethod] ?? null;
        if ($method === null) {
            throw new HttpMethodNotAllowedException(
                message: (new Message('Unknown HTTP method `%httpMethod%` provided for %controller% controller.'))
                    ->withCode('%httpMethod%', $httpMethod)
                    ->withCode('%controller%', $httpController::class)
            );
        }
        if ($middleware !== null) {
            $httpController = $httpController->withMiddleware(
                $httpController->middleware()->withPrepend(
                    ...iterator_to_array($middleware->getIterator())
                )
            );
        }
        /** @var MethodInterface $method */
        $method = new $method();
        $route = $route->withEndpoint(
            new Endpoint($method, $httpController)
        );
    }

    return $route;
}

/**
 * @throws NotRoutableException
 * @throws WithoutEndpointsException
 * @throws InvalidArgumentException
 * @throws OverflowException
 *
 * @codeCoverageIgnore
 */
function router(string $group, RoutesInterface $routes): RouterInterface
{
    $router = new Router();
    foreach ($routes->getIterator() as $route) {
        $router = $router->withAddedRoute($group, $route);
    }

    return $router;
}

/**
 * @throws FilesystemException
 * @throws FileNotExistsException
 * @throws FileUnableToGetException
 * @throws FileWithoutContentsException
 * @throws FileInvalidContentsException
 * @throws RuntimeException
 * @throws FileReturnInvalidTypeException
 *
 * @codeCoverageIgnore
 */
function importRoutes(string $path): RoutesInterface
{
    /** @var RoutesInterface */
    return filePhpReturnForPath($path)
        ->variableTyped(new Type(RoutesInterface::class));
}
