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

use Chevere\Action\Action;
use Chevere\DataStructure\Interfaces\MapInterface;
use Chevere\DataStructure\Map;
use Chevere\Http\Interfaces\ControllerInterface;
use Chevere\Http\Interfaces\StatusInterface;
use Chevere\Http\Status;
use Chevere\Parameter\Interfaces\ArgumentsInterface;
use Chevere\Parameter\Interfaces\ArrayParameterInterface;
use Chevere\Parameter\Interfaces\ArrayStringParameterInterface;
use Chevere\Parameter\Interfaces\CastInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function Chevere\Parameter\arguments;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\arrayString;
use function Chevere\Parameter\cast;

final class WrongController extends Action implements ControllerInterface
{
    public static function acceptQuery(): ArrayStringParameterInterface
    {
        return arrayString();
    }

    public static function acceptBody(): ArrayParameterInterface
    {
        return arrayp();
    }

    public static function acceptFiles(): ArrayParameterInterface
    {
        return arrayp();
    }

    public function withServerRequest(ServerRequestInterface $serverRequest): static
    {
        return $this;
    }

    public function query(): ArgumentsInterface
    {
        return arguments(
            static::acceptQuery()->parameters(),
            []
        );
    }

    public function body(): ArgumentsInterface
    {
        return arguments(
            static::acceptBody()->parameters(),
            []
        );
    }

    public function files(): ArgumentsInterface
    {
        return arguments(
            static::acceptFiles()->parameters(),
            []
        );
    }

    public function terminate(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    public function serverParams(): MapInterface
    {
        return new Map();
    }

    public function attributes(): MapInterface
    {
        return new Map();
    }

    public function attribute(string $name, mixed $default = null): CastInterface
    {
        return cast(null);
    }

    public function status(): StatusInterface
    {
        return new Status();
    }

    protected function main(int $id): array
    {
        return [];
    }
}
