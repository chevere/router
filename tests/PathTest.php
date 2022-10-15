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

use Chevere\Router\Path;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
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
        $this->assertTrue($path->wildcards()->has('id'));
        $this->assertTrue($path->wildcards()->has('var'));
        $this->assertSame('/path/{id}/it/{var}', $path->name());
        $this->assertSame(
            '~^(?|/path/(\d+)/it/([^/]+))$~',
            $path->regex()->__toString()
        );
        $this->assertSame($string, $path->__toString());
    }
}
