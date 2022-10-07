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

namespace Chevere\Router\Route;

use Chevere\Message\Message;
use Chevere\Regex\Regex;
use Chevere\Router\Interfaces\Route\RouteWildcardMatchInterface;
use Chevere\String\ValidateString;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\UnexpectedValueException;

final class RouteWildcardMatch implements RouteWildcardMatchInterface
{
    public function __construct(
        private string $string
    ) {
        $this->assertFormat();
        $this->assertRegexNoCapture();
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function toAnchored(): string
    {
        return '^' . $this->string . '$';
    }

    public function assertRegexNoCapture(): void
    {
        $regex = new Regex('#' . $this->string . '#');
        $string = $regex->__toString();
        $regex = str_replace(['\(', '\)'], '', $string);
        if (strpos($regex, '(') !== false || strpos($regex, ')') !== false) {
            throw new UnexpectedValueException(
                (new Message('Provided expression %match% contains capture groups'))
                    ->withCode('%match%', $string)
            );
        }
    }

    private function assertFormat(): void
    {
        if ((new ValidateString($this->string))->isStartingWith('^')) {
            throw new InvalidArgumentException(
                (new Message('String %string% must omit the starting anchor %char%'))
                    ->withCode('%string%', $this->string)
                    ->withCode('%char%', '^')
            );
        }
        if ((new ValidateString($this->string))->isEndingWith('$')) {
            throw new InvalidArgumentException(
                (new Message('String %string% must omit the ending anchor %char%'))
                    ->withCode('%string%', $this->string)
                    ->withCode('%char%', '$')
            );
        }
    }
}
