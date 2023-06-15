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

use Chevere\Router\Exceptions\VariableInvalidException;
use Chevere\Router\Variable;
use Chevere\Router\VariableRegex;
use FastRoute\RouteParser\Std;
use PHPUnit\Framework\TestCase;

final class VariableTest extends TestCase
{
    public function testConstructVariableStartsWithInvalidChar(): void
    {
        $this->expectException(VariableInvalidException::class);
        new Variable('0test', new VariableRegex(Std::DEFAULT_DISPATCH_REGEX));
    }

    public function testConstructVariableInvalidChars(): void
    {
        $this->expectException(VariableInvalidException::class);
        new Variable('t{e/s}t', new VariableRegex(Std::DEFAULT_DISPATCH_REGEX));
    }

    public function testWithRegex(): void
    {
        $name = 'test';
        $match = new VariableRegex('[a-z]+');
        $variable = new Variable($name, $match);
        $this->assertSame($name, $variable->__toString());
        $this->assertSame($match, $variable->variableRegex());
    }
}
