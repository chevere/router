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
use Chevere\Filesystem\Exceptions\FileInvalidContentsException;
use Chevere\Filesystem\Exceptions\FileNotExistsException;
use Chevere\Filesystem\Exceptions\FileReturnInvalidTypeException;
use Chevere\Filesystem\Exceptions\FilesystemException;
use Chevere\Filesystem\Exceptions\FileUnableToGetException;
use Chevere\Filesystem\Exceptions\FileWithoutContentsException;
use function Chevere\Filesystem\filePhpReturnForPath;
use Chevere\Http\Exceptions\HttpMethodNotAllowedException;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Exceptions\NotRoutableException;
use Chevere\Router\Exceptions\WithoutEndpointsException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OverflowException;
use Chevere\Throwable\Exceptions\RuntimeException;
use Chevere\Type\Type;
use Psr\Http\Server\MiddlewareInterface;

function routes(RouteInterface ...$namedRoutes): RoutesInterface
{
    return (new Routes())->withAdded(...$namedRoutes);
}

/**
 * @param string $path Route path.
 * @param string $name If not provided it will be same as the route path.
 * @param string $view View binding.
 * @param array<MiddlewareInterface> $middleware Route middleware.
 * @param HttpControllerInterface ...$httpControllers Binding for METHOD: CONTROLLER pairs.
 */
function route(
    string $path,
    string $name = '',
    string $view = '',
    array $middleware = [],
    HttpControllerInterface ...$httpControllers
): RouteInterface {
    $name = $name === '' ? $path : $name;
    $firstControllerKey = array_key_first($httpControllers);
    if ($firstControllerKey !== null) {
        $firstController = $httpControllers[$firstControllerKey];
        $routePath = new Path($path);
        foreach ($routePath->wildcards()->keys() as $wildcard) {
            /** @var StringParameterInterface $controllerParameter */
            $controllerParameter = $firstController->parameters()->get($wildcard);
            $path = str_replace(
                '{' . $wildcard . '}',
                '{' . $wildcard . ':'
                    . $controllerParameter->regex()->noDelimitersNoAnchors()
                    . '}',
                $path
            );
        }
    }
    $route = (new Route(new Path($path), $name, $view))
        ->withMiddleware(...$middleware);
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
