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

use Chevere\Caller\Caller;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Interfaces\MiddlewaresInterface;
use Chevere\Http\Middlewares;
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\VariableConflictException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\EndpointsInterface;
use Chevere\Router\Interfaces\PathInterface;
use Chevere\Router\Interfaces\RouteInterface;
use InvalidArgumentException;
use OutOfBoundsException;
use OverflowException;
use function Chevere\Message\message;
use function Chevere\Parameter\string;

final class Route implements RouteInterface
{
    private EndpointInterface $firstEndpoint;

    private EndpointsInterface $endpoints;

    private Caller $caller;

    public function __construct(
        private PathInterface $path,
        private string $name,
        private MiddlewaresInterface $excluded = new Middlewares()
    ) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 2); // @codeCoverageIgnore
        $callerFunction = $backtrace[1]['function'] ?? '';
        $index = intval($callerFunction === 'Chevere\Router\route');
        $this->caller = new Caller(
            $backtrace[$index]['file'], // @phpstan-ignore-line
            $backtrace[$index]['line'] // @phpstan-ignore-line
        );
        $this->endpoints = new Endpoints();
    }

    public function caller(): Caller
    {
        return $this->caller;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): PathInterface
    {
        return $this->path;
    }

    public function excluded(): MiddlewaresInterface
    {
        return $this->excluded;
    }

    public function withEndpoint(EndpointInterface $endpoint): RouteInterface
    {
        $new = clone $this;
        if (! isset($new->firstEndpoint)) {
            $new->firstEndpoint = $endpoint;
        }
        $new->assertUnique($endpoint);
        $new->assertNoConflict($endpoint);
        $controllerFqn = $endpoint->bind()->controllerName()->__toString();
        $parameters = $controllerFqn::reflection()->parameters();
        $new->assertVariableBounds($parameters, $controllerFqn);
        $defaultStringRegex = string()->regex()->noDelimitersNoAnchors();
        foreach ($new->path->variables() as $variable) {
            $new->assertEndpoint($endpoint);
            /** @var StringParameterInterface $parameter */
            $parameter = $parameters->get(strval($variable));
            $parameterRegex = $parameter->regex()->noDelimitersNoAnchors();
            if ($parameterRegex === $defaultStringRegex) {
                $parameterRegex = '[^/]+';
            }
            $variableRegex = strval($variable->regex());
            $variableString = strval($variable);
            if (strpos(strval($this->path), $variableString . '}') !== false
                || $variableRegex === $defaultStringRegex
            ) {
                $variableRegex = $parameterRegex; // @codeCoverageIgnore
            }
            if ($parameterRegex !== $variableRegex) {
                throw new VariableConflictException(
                    (string) message(
                        <<<MESSAGE
                        Variable `%parameter%` matches against `%match%` which is incompatible with the match `%controllerRegex%` defined by `%controller%`
                        MESSAGE,
                        parameter: '{' . strval($variable) . '}',
                        match: $variableRegex,
                        controllerRegex: $parameterRegex,
                        controller: $endpoint->bind()->controllerName()->__toString(),
                    )
                );
            }
        }
        $new->endpoints = $new->endpoints->with($endpoint);

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

    private function assertVariableBounds(ParametersInterface $parameters, string $controller): void
    {
        $diff = array_diff(
            $parameters->keys(),
            $this->path->variables()->keys()
        );
        if ($diff === []) {
            return;
        }

        throw new OutOfBoundsException(
            (string) message(
                'Unmatched path `%path%` parameter(s) `%parameters%` for **%controller%**',
                parameters: implode(', ', $diff),
                path: $this->path->__toString(),
                controller: $controller,
            )
        );
    }

    private function assertUnique(EndpointInterface $endpoint): void
    {
        $key = $endpoint->method()->name();
        if ($this->endpoints->has($key)) {
            throw new OverflowException(
                (string) message(
                    'Endpoint for method `%method%` has been already added',
                    method: $key
                )
            );
        }
    }

    private function assertNoConflict(EndpointInterface $endpoint): void
    {
        if (count($this->endpoints()) === 0) {
            return;
        }
        $firstControllerName = $this->firstEndpoint->bind()->controllerName()->__toString();
        $parameters = $firstControllerName::reflection()->parameters();
        /** @var StringParameterInterface $parameter */
        foreach ($parameters as $name => $parameter) {
            $match = $parameter->regex()->__toString();
            $controllerName = $endpoint->bind()->controllerName()->__toString();

            try {
                $string = $controllerName::reflection()->parameters()->required($name)->string();
                $controllerRegex = $string->regex()->__toString();
            } catch (OutOfBoundsException) {
                $controllerRegex = '<none>';
            }
            if ($match !== $controllerRegex) {
                throw new EndpointConflictException(
                    (string) message(
                        <<<MESSAGE
                        Controller parameter `%parameter%` first defined at `%firstController%` matches against `%match%` which is incompatible with the match `%controllerRegex%` defined by `%controller%`
                        MESSAGE,
                        parameter: $name,
                        match: $match,
                        controllerRegex: $controllerRegex,
                        controller: $controllerName,
                        firstController: $this->firstEndpoint->bind()->controllerName()->__toString(),
                    )
                );
            }
        }
    }

    private function assertEndpoint(EndpointInterface $endpoint): void
    {
        $parameters = $endpoint->bind()->controllerName()->__toString()::reflection()->parameters();
        if (count($parameters) === 0) {
            throw new InvalidArgumentException(
                (string) message(
                    "Invalid route` %path% `binding with `%controller%` which doesn't accept any parameter",
                    path: $this->path->__toString(),
                    controller: $endpoint->bind()->controllerName()->__toString(),
                )
            );
        }
    }
}
