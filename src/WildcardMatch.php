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
use Chevere\Regex\Regex;
use Chevere\Router\Interfaces\WildcardMatchInterface;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\UnexpectedValueException;

final class WildcardMatch implements WildcardMatchInterface
{
    private string $anchored;

    public function __construct(
        private string $string
    ) {
        $this->assertFormat();
        $this->assertRegexNoCapture();
        $this->anchored = '^' . $this->string . '$';
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function anchored(): string
    {
        return $this->anchored;
    }

    private function assertRegexNoCapture(): void
    {
        $regex = new Regex('#' . $this->string . '#');
        $string = $regex->__toString();
        if (strpos($string, '(') !== false) {
            throw new UnexpectedValueException(
                (new Message('Provided expression %match% contains capture groups'))
                    ->withCode('%match%', $string)
            );
        }
    }

    private function assertFormat(): void
    {
        if (str_starts_with($this->string, '^')) {
            throw new InvalidArgumentException(
                (new Message('String %string% must omit the starting anchor %char%'))
                    ->withCode('%string%', $this->string)
                    ->withCode('%char%', '^')
            );
        }
        if (str_ends_with($this->string, '$')) {
            throw new InvalidArgumentException(
                (new Message('String %string% must omit the ending anchor %char%'))
                    ->withCode('%string%', $this->string)
                    ->withCode('%char%', '$')
            );
        }
    }
}
