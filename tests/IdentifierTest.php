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

use Chevere\Router\Identifier;
use Chevere\String\Exceptions\CtypeSpaceException;
use Chevere\String\Exceptions\EmptyException;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    public function testConstruct(): void
    {
        $group = 'some-group';
        $name = 'some-name';
        $routeIdentifier = new Identifier($group, $name);
        $this->assertSame($group, $routeIdentifier->group());
        $this->assertSame($name, $routeIdentifier->id());
        $this->assertSame([
            'group' => $group,
            'name' => $name,
        ], $routeIdentifier->toArray());
    }

    public function testEmptyGroup(): void
    {
        $this->expectException(EmptyException::class);
        new Identifier('', 'some-name');
    }

    public function testCtypeSpaceGroup(): void
    {
        $this->expectException(CtypeSpaceException::class);
        new Identifier('   ', 'some-name');
    }

    public function testEmptyName(): void
    {
        $this->expectException(EmptyException::class);
        new Identifier('some-group', '');
    }

    public function testCtypeSpaceName(): void
    {
        $this->expectException(CtypeSpaceException::class);
        new Identifier('some-group', '  ');
    }
}
