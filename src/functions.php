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

use Chevere\Controller\Interfaces\ControllerInterface;
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

function routes(RouteInterface ...$namedRoutes): RoutesInterface
{
    return (new Routes())->withAdded(...$namedRoutes);
}

/**
 * @param ?string $name The route name, if not provided it will be same as the route path.
 * @param string $path The route path.
 * @param array<string> $middleware The route middleware(s).
 * @param ControllerInterface ...$httpControllers Named arguments for httpMethod: Controller as `POST: PostController`.
 */
function route(
    string $path,
    ?string $name = null,
    ?string $view = null,
    array $middleware = [],
    ControllerInterface ...$httpControllers
): RouteInterface {
    $route = (new Route(new Path($path), $name ?? $path, $view ?? ''))
        ->withMiddleware(...$middleware);
    foreach ($httpControllers as $httpMethod => $controller) {
        $httpMethod = strval($httpMethod);
        $method = EndpointInterface::KNOWN_METHODS[$httpMethod] ?? null;
        if ($method === null) {
            throw new HttpMethodNotAllowedException(
                message: (new Message('Unknown HTTP method `%httpMethod%` provided for %controller% controller.'))
                    ->withCode('%httpMethod%', $httpMethod)
                    ->withCode('%controller%', $controller::class)
            );
        }
        /** @var MethodInterface $method */
        $method = new $method();
        $route = $route->withEndpoint(
            new Endpoint($method, $controller)
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
