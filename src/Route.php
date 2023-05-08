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

use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Http\Middlewares;
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
    private EndpointInterface $firstEndpoint;

    private EndpointsInterface $endpoints;

    private MiddlewaresInterface $middlewares;

    public function __construct(
        private PathInterface $path,
        private string $name,
    ) {
        $this->endpoints = new Endpoints();
        $this->middlewares = new Middlewares();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): PathInterface
    {
        return $this->path;
    }

    public function withEndpoint(EndpointInterface $endpoint): RouteInterface
    {
        $new = clone $this;
        if (! isset($new->firstEndpoint)) {
            $new->firstEndpoint = $endpoint;
        }
        $new->assertUnique($endpoint);
        $new->assertNoConflict($endpoint);
        foreach ($new->path->wildcards() as $wildcard) {
            $new->assertWildcardEndpoint($wildcard, $endpoint);
            /** @var StringParameterInterface $parameter */
            $parameter = $endpoint->bind()->controllerName()->__toString()::getParameters()
                ->get(strval($wildcard));
            $parameterMatch = $parameter->regex()->noDelimitersNoAnchors();
            $wildcardMatch = strval($wildcard->match());
            $wildcardString = strval($wildcard);
            if (strpos(strval($this->path), $wildcardString . '}') !== false) {
                $wildcardMatch = $parameterMatch; // @codeCoverageIgnore
            }
            if ($parameterMatch !== $wildcardMatch) {
                throw new WildcardConflictException(
                    (new Message('Wildcard %parameter% matches against %match% which is incompatible with the match %controllerMatch% defined by %controller%'))
                        ->withCode('%parameter%', '{' . strval($wildcard) . '}')
                        ->withCode('%match%', $wildcardMatch)
                        ->withCode('%controllerMatch%', $parameterMatch)
                        ->withCode('%controller%', $endpoint->bind()->controllerName()->__toString())
                );
            }
        }
        $new->endpoints = $new->endpoints->withPut($endpoint);

        return $new;
    }

    public function withoutEndpoint(MethodInterface $method): RouteInterface
    {
        $new = clone $this;
        $new->endpoints = $new->endpoints->without($method->name());
        if (count($new->endpoints) > 0) {
            $new->firstEndpoint = $new->endpoints->get($new->endpoints->keys()[0]);
        }

        return $new;
    }

    public function endpoints(): EndpointsInterface
    {
        return $this->endpoints;
    }

    public function withMiddlewares(MiddlewaresInterface $middlewares): RouteInterface
    {
        $new = clone $this;
        $new->middlewares = $middlewares;

        return $new;
    }

    public function middlewares(): MiddlewaresInterface
    {
        return $this->middlewares;
    }

    private function assertUnique(EndpointInterface $endpoint): void
    {
        $key = $endpoint->method()->name();
        if ($this->endpoints->has($key)) {
            throw new OverflowException(
                (new Message('Endpoint for method %method% has been already added'))
                    ->withCode('%method%', $key)
            );
        }
    }

    private function assertNoConflict(EndpointInterface $endpoint): void
    {
        if (count($this->endpoints()) === 0) {
            return;
        }
        /** @var StringParameterInterface $parameter */
        foreach ($this->firstEndpoint->bind()->controllerName()->__toString()::getParameters() as $name => $parameter) {
            $match = $parameter->regex()->__toString();

            try {
                /** @var StringParameterInterface $string */
                $string = $endpoint->bind()->controllerName()->__toString()::getParameters()->get($name);
                $controllerMatch = $string->regex()->__toString();
            } catch(OutOfBoundsException) {
                $controllerMatch = '<none>';
            }
            if ($match !== $controllerMatch) {
                throw new EndpointConflictException(
                    (new Message('Controller parameter %parameter% first defined at %firstController% matches against %match% which is incompatible with the match %controllerMatch% defined by %controller%'))
                        ->withCode('%parameter%', $name)
                        ->withCode('%match%', $match)
                        ->withCode('%controllerMatch%', $controllerMatch)
                        ->withCode('%controller%', $endpoint->bind()->controllerName()->__toString())
                        ->withCode('%firstController%', $this->firstEndpoint->bind()->controllerName()->__toString())
                );
            }
        }
    }

    private function assertWildcardEndpoint(WildcardInterface $wildcard, EndpointInterface $endpoint): void
    {
        $parameters = $endpoint->bind()->controllerName()->__toString()::getParameters();
        if (count($parameters) === 0) {
            throw new InvalidArgumentException(
                (new Message("Invalid route %path% binding with %controller% which doesn't accept any parameter"))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%controller%', $endpoint->bind()->controllerName()->__toString())
                    ->withCode('%wildcard%', $wildcard->__toString())
            );
        }

        if (! $parameters->has($wildcard->__toString())) {
            throw new OutOfBoundsException(
                (new Message('Route %path% must bind to one of the known %controller% parameters: %parameters%'))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%wildcard%', $wildcard->__toString())
                    ->withCode('%controller%', $endpoint->bind()->controllerName()->__toString())
                    ->withCode('%parameters%', implode(', ', $parameters->keys()))
            );
        }
    }
}
