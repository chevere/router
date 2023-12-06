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

/**
 * Describes the component in charge of describing the route identifier.
 */
interface IdentifierInterface
{
    public function group(): string;

    public function id(): string;

    /**
     * @return array<string, string>
     */
    public function toArray(): array;
}
