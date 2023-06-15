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
use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Interfaces\VariableInterface;
use Chevere\Router\Interfaces\VariableRegexInterface;

final class Variable implements VariableInterface
{
    public function __construct(
        private string $name,
        private VariableRegexInterface $regex
    ) {
        $this->assertName();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function regex(): VariableRegexInterface
    {
        return $this->regex;
    }

    private function assertName(): void
    {
        if (! preg_match(VariableInterface::ACCEPT_CHARS_REGEX, $this->name)) {
            throw new VariableInvalidException(
                (new Message('String %string% must contain only alphanumeric and underscore characters'))
                    ->withCode('%string%', $this->name)
            );
        }
    }
}
