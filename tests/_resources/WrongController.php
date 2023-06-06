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

namespace Chevere\Tests\_resources;

use Chevere\Action\Traits\ActionTrait;
use Chevere\Http\Interfaces\ControllerInterface;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\arrayString;
use Chevere\Parameter\Interfaces\ArrayStringParameterInterface;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;

final class WrongController implements ControllerInterface
{
    use ActionTrait;

    public function run(int $id): array
    {
        return [];
    }

    public function assert(): void
    {
    }

    public static function acceptQuery(): ArrayStringParameterInterface
    {
        return arrayString();
    }

    public static function acceptBody(): ArrayTypeParameterInterface
    {
        return arrayp();
    }

    public static function acceptFiles(): ArrayTypeParameterInterface
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

    public function query(): array
    {
        return [];
    }

    public function body(): array
    {
        return [];
    }

    public function files(): array
    {
        return [];
    }
}
