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

use Chevere\Controller\ControllerName;
use Chevere\Http\Exceptions\HttpMethodNotAllowedException;
use Chevere\Message\Message;
use Chevere\Router\Exceptions\NotFoundException;
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

    public function dispatch(string $httpMethod, string $uri): RoutedInterface
    {
        $info = (new GroupCountBased($this->routeCollector->getData()))
            ->dispatch($httpMethod, $uri);

        return match ($info[0]) {
            GroupCountBased::NOT_FOUND =>
                throw new NotFoundException(
                    (new Message('No route found for %uri%'))
                        ->withCode('%uri%', $uri)
                ),
            GroupCountBased::FOUND => new Routed(new ControllerName($info[1]), $info[2]),
            GroupCountBased::METHOD_NOT_ALLOWED =>
                throw new HttpMethodNotAllowedException(
                    (new Message('Method %method% is not in the list of allowed methods: %allowed%'))
                        ->withCode('%method%', $httpMethod)
                        ->withCode('%allowed%', implode(', ', $info[1]))
                ),
            default => throw new LogicException(
                (new Message('Unknown route status %status%'))
                    ->withCode('%status%', $info[0])
            ),
        };
    }
}
