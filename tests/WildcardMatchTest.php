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

namespace Chevere\Router\Tests;

use Chevere\Router\WildcardMatch;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\UnexpectedValueException;
use PHPUnit\Framework\TestCase;

final class WildcardMatchTest extends TestCase
{
    public function testConstructInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WildcardMatch('#');
    }

    public function testConstructInvalidArgument2(): void
    {
        $this->expectException(UnexpectedValueException::class);
        new WildcardMatch('te(s)t');
    }

    public function testConstructWithAnchorStart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WildcardMatch('^error');
    }

    public function testConstructWithAnchorEnd(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WildcardMatch('error$');
    }

    public function testConstruct(): void
    {
        $string = '[a-z]+';
        $wildcardMatch = new WildcardMatch($string);
        $this->assertSame($string, $wildcardMatch->__toString());
        $this->assertSame('^' . $string . '$', $wildcardMatch->anchored());
    }
}
