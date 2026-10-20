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

use Chevere\Regex\Interfaces\RegexInterface;
use Chevere\Regex\Regex;
use Chevere\Router\Interfaces\VariableRegexInterface;
use InvalidArgumentException;
use UnexpectedValueException;

final class VariableRegex implements VariableRegexInterface
{
    private RegexInterface $regex;

    public function __construct(
        private string $string
    ) {
        $this->assertFormat();
        $this->regex = new Regex("#{$string}#");
        $this->assertRegexNoCapture();
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function noDelimiters(): string
    {
        return $this->regex->noDelimiters();
    }

    private function assertRegexNoCapture(): void
    {
        $string = $this->regex->__toString();
        if (strpos($string, '(') !== false) {
            throw new UnexpectedValueException(
                sprintf(
                    'Provided expression `%s` contains capture groups',
                    $string
                )
            );
        }
    }

    private function assertFormat(): void
    {
        if (str_starts_with($this->string, '^')) {
            throw new InvalidArgumentException(
                sprintf(
                    'String `%s` must omit the starting anchor `%s`',
                    $this->string,
                    '^'
                )
            );
        }
        if (str_ends_with($this->string, '$')) {
            throw new InvalidArgumentException(
                sprintf(
                    'String `%s` must omit the ending anchor `%s`',
                    $this->string,
                    '$'
                )
            );
        }
    }
}
