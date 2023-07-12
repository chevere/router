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

use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Message\Message;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\DispatcherInterface;
use Chevere\Router\Interfaces\RoutedInterface;
use Chevere\Throwable\Exceptions\LogicException;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;

final class Dispatcher implements DispatcherInterface
{
    public function __construct(
        private RouteCollector $routeCollector
    ) {
    }

    // @infection-ignore-all
    public function dispatch(string $httpMethod, string $uri): RoutedInterface
    {
        $info = (new GroupCountBased($this->routeCollector->getData()))
            ->dispatch($httpMethod, $uri);
        /** @var int $status */
        $status = $info[0];
        /** @var BindInterface $handler */
        $handler = $info[1] ?? null;
        /** @var string[] $allowed */
        $allowed = $info[2] ?? [];
        /** @var array<string, string> $arguments */
        $arguments = $info[2] ?? [];

        return match ($status) {
            GroupCountBased::FOUND => new Routed($handler, $arguments),
            GroupCountBased::NOT_FOUND => throw new NotFoundException(
                (new Message('No route found for %uri%'))
                    ->withCode('%uri%', $uri)
            ),
            GroupCountBased::METHOD_NOT_ALLOWED => throw new MethodNotAllowedException(
                (new Message('Method %method% is not in the list of allowed methods: %allowed%'))
                    ->withCode('%method%', $httpMethod)
                    ->withCode('%allowed%', implode(', ', $allowed))
            ),
            // @codeCoverageIgnoreStart
            default => throw new LogicException(
                (new Message('Unknown router status code %status%'))
                    ->withCode('%status%', strval($status))
            ),
            // @codeCoverageIgnoreEnd
        };
    }
}
