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

use Chevere\Router\Path;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    public function testInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Path('[{path}]-invalid');
    }

    public function testInvalidOptionalPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Path('invalid-[path]');
    }

    public function testConstruct(): void
    {
        $string = '/path/{id:\d+}/it/{var}';
        $path = new Path($string);
        $this->assertTrue($path->variables()->has('id'));
        $this->assertTrue($path->variables()->has('var'));
        $this->assertSame('/path/{id}/it/{var}', $path->handle());
        $this->assertSame(
            '~^(?|/path/(\d+)/it/([^/]+))$~',
            $path->regex()->__toString()
        );
        $this->assertSame($string, $path->__toString());
    }

    public function testConstructNoVariables(): void
    {
        $string = '/path';
        $path = new Path($string);
        $this->assertSame('/path', $path->regex()->noDelimiters());
    }
}
