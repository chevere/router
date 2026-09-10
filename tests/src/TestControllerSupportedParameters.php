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
use Chevere\Parameter\ArgumentsString;
use Chevere\Parameter\Interfaces\ArgumentsInterface;
use Chevere\Parameter\Interfaces\ArgumentsStringInterface;
use Chevere\Parameter\Interfaces\ArrayParameterInterface;
use Chevere\Parameter\Interfaces\ArrayStringParameterInterface;
use Chevere\Parameter\Interfaces\TypedInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use function Chevere\Parameter\arguments;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\arrayString;
use function Chevere\Parameter\typed;
use function Chevere\Writer\streamTemp;

final class TestControllerSupportedParameters extends Action implements ControllerInterface
{
    public function __invoke(int $id, string $name, float $rate): array
    {
        return [];
    }

    public static function acceptHeaders(): ArrayStringParameterInterface
    {
        return arrayString();
    }

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

    public function query(): ArgumentsStringInterface
    {
        return new ArgumentsString(
            static::acceptQuery()->parameters(),
            []
        );
    }

    public function bodyParsed(): ArgumentsInterface
    {
        return arguments(
            static::acceptBody()->parameters(),
            []
        );
    }

    public function bodyStream(): StreamInterface
    {
        return streamTemp();
    }

    public function body(): TypedInterface
    {
        return typed('');
    }

    public function files(): ArgumentsInterface
    {
        return arguments(
            static::acceptFiles()->parameters(),
            []
        );
    }

    public function uploadedFiles(): MapInterface
    {
        return new Map();
    }

    public function terminate(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    public function serverParams(): MapInterface
    {
        return new Map();
    }

    public function headers(): ArgumentsStringInterface
    {
        return new ArgumentsString(
            static::acceptHeaders()->parameters(),
            []
        );
    }

    public function cookieParams(): MapInterface
    {
        return new Map();
    }

    public function attributes(): MapInterface
    {
        return new Map();
    }

    public function status(): StatusInterface
    {
        return new Status();
    }
}
