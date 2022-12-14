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

use Chevere\Router\Interfaces\LocatorInterface;

final class Locator implements LocatorInterface
{
    private string $string;

    public function __construct(
        private string $repository,
        private string $path
    ) {
        $this->string = "{$repository}:{$path}";
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function repository(): string
    {
        return $this->repository;
    }

    public function path(): string
    {
        return $this->path;
    }
}
