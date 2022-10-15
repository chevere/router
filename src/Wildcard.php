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

use Chevere\Message\Message;
use Chevere\Router\Exceptions\WildcardInvalidException;
use Chevere\Router\Interfaces\WildcardInterface;
use Chevere\Router\Interfaces\WildcardMatchInterface;

final class Wildcard implements WildcardInterface
{
    public function __construct(
        private string $name,
        private  WildcardMatchInterface $match
    ) {
        $this->assertName();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function match(): WildcardMatchInterface
    {
        return $this->match;
    }

    private function assertName(): void
    {
        if (! preg_match(WildcardInterface::ACCEPT_CHARS_REGEX, $this->name)) {
            throw new WildcardInvalidException(
                (new Message('String %string% must contain only alphanumeric and underscore characters'))
                    ->withCode('%string%', $this->name)
            );
        }
    }
}
