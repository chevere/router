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

    public function withEndpoint(EndpointInterface $endpoint): RouteInterface
    {
        $new = clone $this;
        if (! isset($new->firstEndpoint)) {
            $new->firstEndpoint = $endpoint;
        }
        $new->assertUnique($endpoint);
        $new->assertNoConflict($endpoint);
        foreach ($new->path->wildcards()->getIterator() as $wildcard) {
            $new->assertWildcardEndpoint($wildcard, $endpoint);
            /** @var StringParameterInterface $parameter */
            $parameter = $endpoint->httpController()->parameters()
                ->get(strval($wildcard));
            $parameterMatch = $parameter->regex()->noDelimitersNoAnchors();
            $wildcardMatch = strval($wildcard->match());
            $wildcardString = strval($wildcard);
            if (strpos(strval($this->path), $wildcardString . '}') !== false) {
                $wildcardMatch = $parameterMatch;
            }
            if ($parameterMatch !== $wildcardMatch) {
                /** @var EndpointInterface $firstEndpoint */
                $firstEndpoint = $new->firstEndpoint;

                throw new WildcardConflictException(
                    (new Message('Wildcard %parameter% matches against %match% which is incompatible with the match %controllerMatch% defined by %controller%'))
                        ->withCode('%parameter%', '{' . strval($wildcard) . '}')
                        ->withCode('%match%', $wildcardMatch)
                        ->withCode('%controllerMatch%', $parameterMatch)
                        ->withCode('%controller%', $endpoint->httpController()::class)
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
        /** @var EndpointInterface $firstEndpoint */
        $firstEndpoint = $this->firstEndpoint;
        foreach ($firstEndpoint->parameters() as $name => $parameter) {
            if ($parameter['regex'] !== $endpoint->parameters()[$name]['regex']) {
                throw new EndpointConflictException(
                    (new Message('Controller parameter %parameter% first defined at %firstController% matches against %match% which is incompatible with the match %controllerMatch% defined by %controller%'))
                        ->withCode('%parameter%', $name)
                        ->withCode('%match%', $parameter['regex'])
                        ->withCode('%controllerMatch%', $endpoint->parameters()[$name]['regex'])
                        ->withCode('%controller%', $endpoint->httpController()::class)
                        ->withCode('%firstController%', $firstEndpoint->httpController()::class)
                );
            }
        }
    }

    private function assertWildcardEndpoint(WildcardInterface $wildcard, EndpointInterface $endpoint): void
    {
        if ($endpoint->httpController()->parameters()->count() === 0) {
            throw new InvalidArgumentException(
                (new Message("Invalid route %path% binding with %controller% which doesn't accept any parameter"))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%controller%', $endpoint->httpController()::class)
                    ->withCode('%wildcard%', $wildcard->__toString())
            );
        }
        if (! array_key_exists($wildcard->__toString(), $endpoint->parameters())) {
            $parameters = array_keys($endpoint->parameters());

            throw new OutOfBoundsException(
                (new Message('Route %path% must bind to one of the known %controller% parameters: %parameters%'))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%wildcard%', $wildcard->__toString())
                    ->withCode('%controller%', $endpoint->httpController()::class)
                    ->withCode('%parameters%', implode(', ', $parameters))
            );
        }
    }
}
