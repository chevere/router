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

final class Route implements RouteInterface
{
    /**
     * @var array<string, string>
     */
    private array $wildcards;

    /**
     * @var array<string>
     */
    private array $middleware = [];

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

    public function withMiddleware(string ...$middleware): RouteInterface
    {
        $new = clone $this;
        $new->middleware = array_values(
            array_unique(
                array_merge($new->middleware, $middleware)
            )
        );

        return $new;
    }

    public function withEndpoint(EndpointInterface $endpoint): RouteInterface
    {
        $new = clone $this;
        $new->assertUnique($endpoint);
        $new->assertNoConflict($endpoint);
        foreach ($new->path->wildcards()->getIterator() as $wildcard) {
            $new->assertWildcardEndpoint($wildcard, $endpoint);
            $wildcardMatch = $new->wildcards[$wildcard->__toString()] ?? null;
            /** @var StringParameterInterface $parameter */
            $parameter = $endpoint->controller()->parameters()->get($wildcard->__toString());
            $parameterMatch = $parameter->regex()->noDelimitersNoAnchors();
            if (! isset($wildcardMatch)) {
                if ($parameterMatch !== $wildcard->match()->__toString()) {
                    throw new WildcardConflictException(
                        (new Message('Wildcard %parameter% matches against %match% which is incompatible with the match %controllerMatch% defined for %controller%'))
                            ->withCode('%parameter%', $wildcard->__toString())
                            ->withCode('%match%', $wildcard->match()->__toString())
                            ->withCode('%controllerMatch%', $parameterMatch)
                            ->withCode('%controller%', $endpoint->controller()::class)
                    );
                }
                $new->wildcards[$wildcard->__toString()] = $parameterMatch;
            }
            $endpoint = $endpoint->withoutParameter($wildcard->__toString());
        }
        $new->endpoints = $new->endpoints->withPut($endpoint);

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
                (new Message("Controller %controller% doesn't accept any parameter (route wildcard %wildcard%)"))
                    ->withCode('%controller%', $endpoint->controller()::class)
                    ->withCode('%wildcard%', $wildcard->__toString())
            );
        }
        if (! array_key_exists($wildcard->__toString(), $endpoint->parameters())) {
            $parameters = array_keys($endpoint->parameters());

            throw new OutOfBoundsException(
                (new Message('Wildcard parameter %wildcard% must bind to one of the known %controller% parameters: %parameters%'))
                    ->withCode('%wildcard%', $wildcard->__toString())
                    ->withCode('%controller%', $endpoint->controller()::class)
                    ->withCode('%parameters%', implode(', ', $parameters))
            );
        }
    }
}
