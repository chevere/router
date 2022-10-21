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

use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\WildcardConflictException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\EndpointsInterface;
use Chevere\Router\Interfaces\PathInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Router\Interfaces\WildcardInterface;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use Psr\Http\Server\MiddlewareInterface;

final class Route implements RouteInterface
{
    /**
     * @var array<MiddlewareInterface>
     */
    private array $middleware = [];

    /**
     * @var array<string>
     */
    private array $middlewareItems = [];

    private ?EndpointInterface $firstEndpoint;

    private EndpointsInterface $endpoints;

    public function __construct(
        private PathInterface $path,
        private string $name,
        private string $view = '',
    ) {
        $this->endpoints = new Endpoints();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): PathInterface
    {
        return $this->path;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function middleware(): array
    {
        return $this->middleware;
    }

    public function withMiddleware(MiddlewareInterface ...$middleware): RouteInterface
    {
        $new = clone $this;
        foreach ($middleware as $item) {
            if (in_array($item::class, $new->middlewareItems, true)) {
                continue;
            }
            $new->middleware[] = $item;
            $new->middlewareItems[] = $item::class;
        }

        return $new;
    }

    public function withEndpoint(EndpointInterface $endpoint): RouteInterface
    {
        $new = clone $this;
        $new->assertUnique($endpoint);
        $new->assertNoConflict($endpoint);
        foreach ($new->path->wildcards()->getIterator() as $wildcard) {
            $new->assertWildcardEndpoint($wildcard, $endpoint);
            /** @var StringParameterInterface $parameter */
            $parameter = $endpoint->controller()->parameters()
                ->get(strval($wildcard));
            $parameterMatch = $parameter->regex()->noDelimitersNoAnchors();
            $wildcardMatch = strval($wildcard->match());
            $wildcardString = strval($wildcard);
            if (strpos(strval($this->path), $wildcardString . '}') !== false) {
                $wildcardMatch = $parameterMatch;
            }
            if ($parameterMatch !== $wildcardMatch) {
                throw new WildcardConflictException(
                    (new Message('Wildcard %parameter% matches against %match% which is incompatible with the match %controllerMatch% defined for %controller%'))
                        ->withCode('%parameter%', strval($wildcard))
                        ->withCode('%match%', $wildcardMatch)
                        ->withCode('%controllerMatch%', $parameterMatch)
                        ->withCode('%controller%', $endpoint->controller()::class)
                );
            }
            $endpoint = $endpoint
                ->withoutParameter(strval($wildcard));
        }
        $new->endpoints = $new->endpoints
            ->withPut($endpoint);

        return $new;
    }

    public function endpoints(): EndpointsInterface
    {
        return $this->endpoints;
    }

    private function assertUnique(EndpointInterface $endpoint): void
    {
        $key = $endpoint->method()->name();
        if ($this->endpoints->hasKey($key)) {
            throw new OverflowException(
                (new Message('Endpoint for method %method% has been already added'))
                    ->withCode('%method%', $key)
            );
        }
    }

    private function assertNoConflict(EndpointInterface $endpoint): void
    {
        if (! isset($this->firstEndpoint)) {
            $this->firstEndpoint = $endpoint;
        } else {
            foreach ($this->firstEndpoint->parameters() as $name => $parameter) {
                if ($parameter['regex'] !== $endpoint->parameters()[$name]['regex']) {
                    throw new EndpointConflictException(
                        (new Message('Controller parameters provided by %provided% must be compatible with the parameters defined first by %defined%'))
                            ->withCode('%provided%', $endpoint->controller()::class)
                            ->withCode('%defined%', $this->firstEndpoint->controller()::class)
                    );
                }
            }
        }
    }

    private function assertWildcardEndpoint(WildcardInterface $wildcard, EndpointInterface $endpoint): void
    {
        if ($endpoint->controller()->parameters()->count() === 0) {
            throw new InvalidArgumentException(
                (new Message("Invalid route %path% binding with %controller% which doesn't accept any parameter"))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%controller%', $endpoint->controller()::class)
                    ->withCode('%wildcard%', $wildcard->__toString())
            );
        }
        if (! array_key_exists($wildcard->__toString(), $endpoint->parameters())) {
            $parameters = array_keys($endpoint->parameters());

            throw new OutOfBoundsException(
                (new Message('Route %path% must bind to one of the known %controller% parameters: %parameters%'))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%wildcard%', $wildcard->__toString())
                    ->withCode('%controller%', $endpoint->controller()::class)
                    ->withCode('%parameters%', implode(', ', $parameters))
            );
        }
    }
}
