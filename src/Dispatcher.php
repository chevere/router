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
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\DispatcherInterface;
use Chevere\Router\Interfaces\RoutedInterface;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use LogicException;
use function Chevere\Message\message;

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
                (string) message(
                    'No route found for `%uri%`',
                    uri: $uri,
                )
            ),
            GroupCountBased::METHOD_NOT_ALLOWED => throw new MethodNotAllowedException(
                (string) message(
                    'Method `%method%` is not in the list of allowed methods: `%allowed%`',
                    method: $httpMethod,
                    allowed: implode(', ', $allowed),
                )
            ),
            // @codeCoverageIgnoreStart
            default => throw new LogicException(
                (string) message(
                    'Unknown router status code `%status%`',
                    status: strval($status),
                )
            ),
            // @codeCoverageIgnoreEnd
        };
    }
}
