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
use Chevere\Router\Interfaces\DispatcherInterface;
use Chevere\Router\Interfaces\DispatchInterface;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use function Chevere\Message\message;

final class Dispatcher implements DispatcherInterface
{
    public function __construct(
        private RouteCollector $routeCollector
    ) {
    }

    // @infection-ignore-all
    public function dispatch(ServerRequestInterface $request): DispatchInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $groupCountBased = new GroupCountBased(
            $this->routeCollector->getData()
        );
        $format = $groupCountBased->dispatch($method, $uri);
        /** @var int 0|1|2 $status */
        $formatCode = $format[0];

        return match ($formatCode) {
            GroupCountBased::FOUND => new Dispatch($format[1], $format[2]),
            GroupCountBased::NOT_FOUND => throw new NotFoundException(
                (string) message(
                    'No route found for %method% `%uri%`',
                    method: $method,
                    uri: $uri,
                ),
                404
            ),
            GroupCountBased::METHOD_NOT_ALLOWED => throw new MethodNotAllowedException(
                (string) message(
                    'Method `%method%` is not in the list of allowed methods: `%allowed%`',
                    method: $method,
                    allowed: implode(', ', $format[1]),
                ),
                405
            ),
            // @codeCoverageIgnoreStart
            default => throw new LogicException(
                (string) message(
                    'Unknown dispatcher format code `%code%`',
                    code: strval($formatCode),
                )
            ),
            // @codeCoverageIgnoreEnd
        };
    }
}
