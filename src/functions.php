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
use Chevere\Http\Exceptions\ControllerException;
use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\Interfaces\ControllerInterface;
use Chevere\Http\Interfaces\ControllerNameInterface;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewareNameInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Http\MiddlewareName;
use Chevere\Http\Middlewares;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Exceptions\VariableNotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RoutedInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use OutOfBoundsException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Relay\Relay;
use Throwable;
use TypeError;
use function Chevere\Action\getParameters;
use function Chevere\Http\middlewares;
use function Chevere\Http\responseAttribute;
use function Chevere\Message\message;

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
        $controllerName = controllerName($item)->__toString();
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
 * @param null|MiddlewaresInterface|MiddlewareNameInterface|class-string<MiddlewareInterface> $middleware HTTP server middleware.
 * @param BindInterface|string ...$bind Binding for HTTP controllers (GET, POST, PUT, DELETE, etc).
 *
 * $bind examples:
 * GET: bind(ClassName, 'view'),
 * POST: bind(ClassName, middleware: Middleware1::class,...),
 * PATCH: ClassName,
 */
function route(
    string $path,
    string $name = '',
    null|string|MiddlewaresInterface|MiddlewareNameInterface $middleware = null,
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
                (string) message(
                    'Unknown HTTP method `%provided%` provided for `%controller%` controller.',
                    provided: $httpMethod,
                    controller: $controllerName->__toString(),
                )
            );
        }
        $isBind = $item instanceof BindInterface;
        $itemView = $isBind
            ? $item->view()
            : '';
        /** @var MethodInterface $object */
        $object = new $method(); // @phpstan-ignore-line
        $middlewares = match (true) {
            $middleware instanceof MiddlewaresInterface => $middleware,
            $middleware === null => middlewares(),
            default => middlewares($middleware),
        };
        if ($item instanceof BindInterface) {
            $middlewares = $middlewares->withAppend(
                ...iterator_to_array(
                    $item->middlewares()
                )
            );
        }
        $bind = (new Bind($controllerName, $middlewares))->withView($itemView);
        $endpoint = new Endpoint($object, $bind);
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
 * Binds a controller to a view and middleware.
 *
 * @param string $controller HTTP controller name
 * @param string $view View name, empty string for headless.
 * @param string $middleware HTTP middleware name(s)
 */
function bind(
    string $controller,
    string $view = '',
    string ...$middleware
): BindInterface {
    $middlewares = [];
    foreach ($middleware as $name) {
        $middlewares[] = new MiddlewareName($name);
    }

    return new Bind(
        new ControllerName($controller),
        new Middlewares(...$middlewares),
        $view
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
 * Executes the request on router.
 *
 * @param array<string, mixed> $container Service container (name => instance).
 */
function routed(
    ServerRequestInterface $serverRequest,
    RouterInterface $router,
    ResponseFactoryInterface $responseFactory = new Psr17Factory(),
    array $container = [],
): RoutedInterface {
    $container['responseFactory'] = $responseFactory;

    try {
        $routed = $router->dispatcher()->dispatch($serverRequest);
    } catch (NotFoundException|MethodNotAllowedException $e) {
        $code = $e instanceof MethodNotAllowedException ? 405 : 404;

        return (new Routed(
            $responseFactory->createResponse($code),
            bind(NullController::class),
        ))->withThrowable($e);
    }
    $queue = [];
    $middlewares = $routed->bind()->middlewares();
    foreach ($middlewares as $middlewareName) {
        $className = (string) $middlewareName;
        $middlewareDependencies = $router->dependencies()->extract($className, $container);
        $middleware = new $className(...$middlewareDependencies);
        if (method_exists($middleware, 'setUp')) {
            $middleware->setUp(...$middlewareName->arguments());
        }
        $queue[$className] = $middleware;
    }
    $handle = new RelayHandle($responseFactory);
    $queue[] = $handle;
    $relay = new Relay($queue);
    $response = $relay->handle($serverRequest);
    $serverRequest = $handle->request();
    $responseHeaders = [];
    foreach ($response->getHeaders() as $name => $values) {
        $responseHeaders[$name] = implode(', ', $values);
    }
    $controllerName = $routed->bind()->controllerName()->__toString();
    $responseAttribute = responseAttribute($controllerName);
    $controllerStatus = $responseAttribute->status->success();
    $controllerHeaders = $responseAttribute->headers->toArray();
    foreach ($controllerHeaders as $name => $value) {
        $response = $response->withHeader($name, $value);
    }
    if ($response->hasHeader('Location')) {
        return new Routed($response, $routed->bind());
    }
    $container = array_merge($container, [
        'request' => $serverRequest,
    ]);
    $controllerArguments = $router->dependencies()->extract($controllerName, $container);
    /** @var ControllerInterface $controller */
    $controller = new $controllerName(...$controllerArguments);

    try {
        $controller = $controller->withServerRequest($serverRequest);
    } catch (Throwable $e) {
        return (new Routed(
            $responseFactory->createResponse(400),
            $routed->bind(),
        ))->withThrowable($e);
    }

    try {
        $controllerResponse = $controller->__invoke(...$routed->arguments());
    } catch (Throwable $e) {
        $code = $e instanceof ControllerException
            ? (int) $e->getCode()
            : 500;

        return (new Routed(
            $responseFactory->createResponse($code),
            $routed->bind(),
        ))->withThrowable($e);
    }
    $response = $responseFactory->createResponse($controllerStatus);
    $headers = array_merge($controllerHeaders, $responseHeaders);
    foreach ($headers as $name => $value) {
        $response = $response->withHeader($name, $value);
    }

    return new Routed(
        $controller->terminate($response),
        $routed->bind(),
        $controllerResponse
    );
}
