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

use Chevere\Router\Identifier;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    public function testConstruct(): void
    {
        $group = 'some-group';
        $name = 'some-name';
        $routeIdentifier = new Identifier($group, $name);
        $this->assertSame($group, $routeIdentifier->group());
        $this->assertSame($name, $routeIdentifier->name());
        $this->assertSame([
            'group' => $group,
            'name' => $name,
        ], $routeIdentifier->toArray());
    }

    public function testEmptyGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ \$group /');
        new Identifier('', 'some-name');
    }

    public function testCtypeSpaceGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ \$group /');
        new Identifier('   ', 'some-name');
    }

    public function testEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ \$name /');
        new Identifier('some-group', '');
    }

    public function testCtypeSpaceName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ \$name /');
        new Identifier('some-group', '  ');
    }
}