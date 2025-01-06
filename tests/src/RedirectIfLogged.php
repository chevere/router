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

namespace Chevere\Tests\src;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RedirectIfLogged implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $isLogged = true; // Check if the user is logged in
        if (! $isLogged) {
            return $handler->handle($request);
        }

        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Location', '/account');
    }
}
