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
use Chevere\Http\Exceptions\ControllerException;
use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Http\Interfaces\ControllerInterface;
use Chevere\Http\Interfaces\ControllerNameInterface;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Http\MiddlewareName;
use Chevere\Http\Middlewares;
use Chevere\Parameter\Arguments;
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Exceptions\VariableNotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\RoutedInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\RouterInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
 * @param null|MiddlewaresInterface|class-string<MiddlewareInterface> $middleware HTTP server middleware.
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
    ServerRequestInterface $request,
    RouterInterface $router,
    array $container = [],
): RoutedInterface {
    $path = $request->getUri()->getPath();
    $body = $request->getParsedBody() ?? [];
    if (! isset($container['response'])) {
        $container['response'] = new Psr17Factory();
    }

    $routed = $router->dispatcher()->dispatch(
        $request->getMethod(),
        $path
    );
    $queue = [];
    $middlewares = $routed->bind()->middlewares();
    foreach ($middlewares as $middlewareName) {
        $className = (string) $middlewareName;
        $middlewareDependencies = $router->dependencies()->extract($className, $container);
        $queue[$className] = new $className(...$middlewareDependencies);
    }
    $queue[] = new class() implements MiddlewareInterface {
        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ): ResponseInterface {
            return new Response();
        }
    };
    $relay = new Relay($queue);
    $response = $relay->handle($request);
    $responseHeaders = [];
    foreach ($response->getHeaders() as $name => $values) {
        $responseHeaders[$name] = implode(', ', $values);
    }
    $controllerName = $routed->bind()->controllerName()->__toString();
    $controllerStatus = responseAttribute($controllerName)->status->primary;
    $controllerHeaders = responseAttribute($controllerName)->headers->toArray();
    foreach ($controllerHeaders as $name => $value) {
        $response = $response->withHeader($name, $value);
    }
    if ($response->hasHeader('Location')) {
        return new Routed(
            $response,
            $routed->bind()->view(),
            null,
        );
    }
    $container = array_merge($container, [
        'request' => $request,
    ]);
    $controllerArguments = $router->dependencies()->extract($controllerName, $container);
    /** @var ControllerInterface $controller */
    $controller = new $controllerName(...$controllerArguments);
    if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
        try {
            $controller = $controller->withBody((array) $body);
        } catch (Throwable $e) {
            return new Routed(
                new Response(status: 400, reason: $e->getMessage()),
                $routed->bind()->view(),
                null
            );
        }
    }

    try {
        $controllerResponse = $controller->__invoke(...$routed->arguments());
    } catch (ControllerException $e) {
        return new Routed(
            new Response(status: $e->getCode(), reason: $e->getMessage()),
            $routed->bind()->view(),
            null
        );
    }
    $response = new Response(
        $controllerStatus,
        array_merge($controllerHeaders, $responseHeaders)
    );

    return new Routed(
        $controller->terminate($response),
        $routed->bind()->view(),
        $controllerResponse
    );
}

/**
 * @param array<string, mixed> $container
 * @return array<string, mixed>
 */
function getDependencies(
    DependenciesInterface $dependencies,
    string $className,
    array $container
): array {
    if (! $dependencies->has($className)) {
        return [];
    }

    return (new Arguments($dependencies->get($className), $container))->toArray();
}
