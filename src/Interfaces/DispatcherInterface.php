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

namespace Chevere\Router\Interfaces;

use Chevere\Http\Exceptions\MethodNotAllowedException;
use Chevere\Router\Exceptions\NotFoundException;
use Chevere\Throwable\Exceptions\LogicException;

/**
 * Describes the component in charge of dispatch router.
 */
interface DispatcherInterface
{
    /**
     * Dispatches against the provided HTTP method verb and URI.
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @throws LogicException if dispatcher returns an unexpected code.
     */
    public function dispatch(string $httpMethod, string $uri): RoutedInterface;
}
