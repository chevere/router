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

namespace Chevere\Router\Route;

use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Exceptions\Route\RouteEndpointConflictException;
use Chevere\Router\Exceptions\Route\RouteWildcardConflictException;
use Chevere\Router\Interfaces\Route\RouteEndpointInterface;
use Chevere\Router\Interfaces\Route\RouteEndpointsInterface;
use Chevere\Router\Interfaces\Route\RouteInterface;
use Chevere\Router\Interfaces\Route\RoutePathInterface;
use Chevere\Router\Interfaces\Route\RouteWildcardInterface;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;

final class Route implements RouteInterface
{
    /**
     * @var array [wildcardName =>]
     */
    private array $wildcards;

    private ?RouteEndpointInterface $firstEndpoint;

    private RouteEndpointsInterface $endpoints;

    public function __construct(
        private string $name,
        private RoutePathInterface $path,
        private string $view = '',
    ) {
        $this->endpoints = new RouteEndpoints();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): RoutePathInterface
    {
        return $this->path;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function withAddedEndpoint(RouteEndpointInterface $endpoint): RouteInterface
    {
        $new = clone $this;
        $new->assertUnique($endpoint);
        $new->assertNoConflict($endpoint);
        foreach ($new->path->wildcards()->getIterator() as $wildcard) {
            $new->assertWildcardEndpoint($wildcard, $endpoint);
            $knownWildcardMatch = $new->wildcards[$wildcard->__toString()] ?? null;
            /** @var StringParameterInterface $controllerParamMatch */
            $controllerParamMatch = $endpoint->controller()->parameters()->get($wildcard->__toString());
            $controllerParamMatch = $controllerParamMatch->regex()->toNoDelimitersNoAnchors();
            if (! isset($knownWildcardMatch)) {
                if ($controllerParamMatch !== $wildcard->match()->__toString()) {
                    throw new RouteWildcardConflictException(
                        (new Message('Wildcard %parameter% matches against %match% which is incompatible with the match %controllerMatch% defined for %controller%'))
                            ->withCode('%parameter%', $wildcard->__toString())
                            ->withCode('%match%', $wildcard->match()->__toString())
                            ->withCode('%controllerMatch%', $controllerParamMatch)
                            ->withCode('%controller%', $endpoint->controller()::class)
                    );
                }
                $new->wildcards[$wildcard->__toString()] = $controllerParamMatch;
            }
            $endpoint = $endpoint->withoutParameter($wildcard->__toString());
        }
        $new->endpoints = $new->endpoints->withPut($endpoint);

        return $new;
    }

    public function endpoints(): RouteEndpointsInterface
    {
        return $this->endpoints;
    }

    private function assertUnique(RouteEndpointInterface $endpoint): void
    {
        $key = $endpoint->method()->name();
        if ($this->endpoints->hasKey($key)) {
            throw new OverflowException(
                (new Message('Endpoint for method %method% has been already added'))
                    ->withCode('%method%', $key)
            );
        }
    }

    private function assertNoConflict(RouteEndpointInterface $endpoint): void
    {
        if (! isset($this->firstEndpoint)) {
            $this->firstEndpoint = $endpoint;
        } else {
            foreach ($this->firstEndpoint->parameters() as $name => $parameter) {
                if ($parameter['regex'] !== $endpoint->parameters()[$name]['regex']) {
                    throw new RouteEndpointConflictException(
                        (new Message('Controller parameters provided by %provided% must be compatible with the parameters defined first by %defined%'))
                            ->withCode('%provided%', $endpoint->controller()::class)
                            ->withCode('%defined%', $this->firstEndpoint->controller()::class)
                    );
                }
            }
        }
    }

    private function assertWildcardEndpoint(RouteWildcardInterface $wildcard, RouteEndpointInterface $endpoint): void
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
