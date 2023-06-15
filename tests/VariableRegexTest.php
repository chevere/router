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

namespace Chevere\Tests;

use Chevere\Router\VariableRegex;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\UnexpectedValueException;
use PHPUnit\Framework\TestCase;

final class VariableRegexTest extends TestCase
{
    public function testConstructInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VariableRegex('#');
    }

    public function testConstructInvalidCaptureGroup(): void
    {
        $this->expectException(UnexpectedValueException::class);
        new VariableRegex('te(s)t');
    }

    public function testConstructWithAnchorStart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VariableRegex('^error');
    }

    public function testConstructWithAnchorEnd(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VariableRegex('error$');
    }

    public function testConstruct(): void
    {
        $string = '[a-z]+';
        $variableRegex = new VariableRegex($string);
        $this->assertSame($string, $variableRegex->__toString());
        $this->assertSame('^' . $string . '$', $variableRegex->noDelimiters());
    }
}
