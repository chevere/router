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
use Chevere\Parameter\Interfaces\ParametersInterface;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\VariableConflictException;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Router\Interfaces\EndpointsInterface;
use Chevere\Router\Interfaces\PathInterface;
use Chevere\Router\Interfaces\RouteInterface;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use function Chevere\Action\getParameters;
use function Chevere\Message\message;

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
        $controllerFqn = $endpoint->bind()->controllerName()->__toString();
        $parameters = getParameters($controllerFqn);
        $new->assertVariableBounds($parameters, $controllerFqn);
        foreach ($new->path->variables() as $variable) {
            $new->assertEndpoint($endpoint);
            /** @var StringParameterInterface $parameter */
            $parameter = $parameters->get(strval($variable));
            $parameterRegex = $parameter->regex()->noDelimitersNoAnchors();
            $variableRegex = strval($variable->regex());
            $variableString = strval($variable);
            if (strpos(strval($this->path), $variableString . '}') !== false) {
                $variableRegex = $parameterRegex; // @codeCoverageIgnore
            }
            if ($parameterRegex !== $variableRegex) {
                throw new VariableConflictException(
                    (new Message(
                        <<<MESSAGE
                        Variable %parameter% matches against %match% which is incompatible with the match %controllerRegex% defined by %controller%
                        MESSAGE
                    ))
                        ->withCode('%parameter%', '{' . strval($variable) . '}')
                        ->withCode('%match%', $variableRegex)
                        ->withCode('%controllerRegex%', $parameterRegex)
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
            message('Unmatched path %path% parameter(s) %parameters% for %controller%')
                ->withCode('%parameters%', implode(', ', $diff))
                ->withCode('%path%', $this->path->__toString())
                ->withStrong('%controller%', $controller)
        );
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
        $firstControllerName = $this->firstEndpoint->bind()->controllerName()->__toString();
        $parameters = getParameters($firstControllerName);
        /** @var StringParameterInterface $parameter */
        foreach ($parameters as $name => $parameter) {
            $match = $parameter->regex()->__toString();
            $controllerName = $endpoint->bind()->controllerName()->__toString();

            try {
                $string = getParameters($controllerName)->required($name)->string();
                $controllerRegex = $string->regex()->__toString();
            } catch (OutOfBoundsException) {
                $controllerRegex = '<none>';
            }
            if ($match !== $controllerRegex) {
                throw new EndpointConflictException(
                    (new Message(
                        <<<MESSAGE
                        Controller parameter %parameter% first defined at %firstController% matches against %match% which is incompatible with the match %controllerRegex% defined by %controller%
                        MESSAGE
                    ))
                        ->withCode('%parameter%', $name)
                        ->withCode('%match%', $match)
                        ->withCode('%controllerRegex%', $controllerRegex)
                        ->withCode('%controller%', $controllerName)
                        ->withCode('%firstController%', $this->firstEndpoint->bind()->controllerName()->__toString())
                );
            }
        }
    }

    private function assertEndpoint(EndpointInterface $endpoint): void
    {
        $parameters = getParameters($endpoint->bind()->controllerName()->__toString());
        if (count($parameters) === 0) {
            throw new InvalidArgumentException(
                (new Message("Invalid route %path% binding with %controller% which doesn't accept any parameter"))
                    ->withCode('%path%', $this->path->__toString())
                    ->withCode('%controller%', $endpoint->bind()->controllerName()->__toString())
            );
        }
    }
}
