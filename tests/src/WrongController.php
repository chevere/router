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
use Chevere\Http\Interfaces\ControllerInterface;
use Chevere\Parameter\Interfaces\ArgumentsInterface;
use Chevere\Parameter\Interfaces\ArrayParameterInterface;
use Chevere\Parameter\Interfaces\ArrayStringParameterInterface;
use function Chevere\Parameter\arguments;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\arrayString;

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

    public function withQuery(array $query): static
    {
        return new self();
    }

    public function withBody(array $body): static
    {
        return new self();
    }

    public function withFiles(array $files): static
    {
        return new self();
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

    public function files(): array
    {
        return [];
    }

    protected function main(int $id): array
    {
        return [];
    }
}
