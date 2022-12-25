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

use Chevere\Router\Exceptions\WildcardInvalidException;
use Chevere\Router\Wildcard;
use Chevere\Router\WildcardMatch;
use FastRoute\RouteParser\Std;
use PHPUnit\Framework\TestCase;

final class WildcardTest extends TestCase
{
    public function testConstructWildcardStartsWithInvalidChar(): void
    {
        $this->expectException(WildcardInvalidException::class);
        new Wildcard('0test', new WildcardMatch(Std::DEFAULT_DISPATCH_REGEX));
    }

    public function testConstructWildcardInvalidChars(): void
    {
        $this->expectException(WildcardInvalidException::class);
        new Wildcard('t{e/s}t', new WildcardMatch(Std::DEFAULT_DISPATCH_REGEX));
    }

    public function testWithRegex(): void
    {
        $name = 'test';
        $match = new WildcardMatch('[a-z]+');
        $wildcard = new Wildcard($name, $match);
        $this->assertSame($name, $wildcard->__toString());
        $this->assertSame($match, $wildcard->match());
    }
}
