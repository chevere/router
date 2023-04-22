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

    public function __construct(
        private PathInterface $path,
        private string $name,
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
            $parameter = $endpoint->bind()->controller()->parameters()
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
                        ->withCode('%controller%', $endpoint->bind()->controller()::class)
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
        foreach ($this->firstEndpoint->bind()->controller()->parameters() as $name => $parameter) {
            $match = $parameter->regex()->__toString();

            try {
                /** @var StringParameterInterface $string */
                $string = $endpoint->bind()->controller()->parameters()->get($name);
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
                        ->withCode('%controller%', $endpoint->bind()->controller()::class)
                        ->withCode('%firstController%', $this->firstEndpoint->bind()->controller()::class)
                );
            }
        }
    }

    private function assertWildcardEndpoint(WildcardInterface $wildcard, EndpointInterface $endpoint): void
    {
        $parameters = $endpoint->bind()->controller()->parameters();
        if (count($parameters) === 0) {
            throw new InvalidArgumentException(
                (new Message("Invalid route %path% binding with %controller% which doesn't accept any parameter"))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%controller%', $endpoint->bind()->controller()::class)
                    ->withCode('%wildcard%', $wildcard->__toString())
            );
        }

        if (! $parameters->has($wildcard->__toString())) {
            throw new OutOfBoundsException(
                (new Message('Route %path% must bind to one of the known %controller% parameters: %parameters%'))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%wildcard%', $wildcard->__toString())
                    ->withCode('%controller%', $endpoint->bind()->controller()::class)
                    ->withCode('%parameters%', implode(', ', $parameters->keys()))
            );
        }
    }
}
