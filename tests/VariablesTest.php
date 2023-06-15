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

use Chevere\Router\Variable;
use Chevere\Router\VariableRegex;
use Chevere\Router\Variables;
use Chevere\Tests\Helpers\ObjectHelper;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use FastRoute\RouteParser\Std;
use PHPUnit\Framework\TestCase;

final class VariablesTest extends TestCase
{
    public function testConstructEmpty(): void
    {
        $variables = new Variables();
        $this->assertCount(0, $variables);
    }

    public function testConstruct(): void
    {
        $name = 'test';
        $variable = new Variable(
            $name,
            new VariableRegex(Std::DEFAULT_DISPATCH_REGEX)
        );
        $variables = (new Variables())->withPut($variable);
        $this->assertCount(1, $variables);
        $this->assertTrue($variables->has($name));
        $this->assertSame($variable, $variables->get($name));
        $this->expectException(OutOfBoundsException::class);
        $variables->get('test2');
    }

    public function testClone(): void
    {
        $variables = new Variables();
        $clone = clone $variables;
        $this->assertNotSame($variables, $clone);
        $helper = new ObjectHelper($variables);
        $cloneHelper = new ObjectHelper($clone);
        foreach (['map'] as $property) {
            $this->assertSame(
                $helper->getPropertyValue($property),
                $cloneHelper->getPropertyValue($property)
            );
        }
    }

    public function testWithAddedVariable(): void
    {
        $match = new VariableRegex(Std::DEFAULT_DISPATCH_REGEX);
        $variablesArray = [new Variable('test1', $match), new Variable('test2', $match)];
        $variables = new Variables();
        foreach ($variablesArray as $variable) {
            $variablesWithPut = ($variablesWithPut ?? $variables)
                ->withPut($variable)
                ->withPut($variable);
            $this->assertNotSame($variables, $variablesWithPut);
        }
        $this->assertCount(2, $variablesWithPut);
        foreach ($variablesArray as $variable) {
            $this->assertTrue($variablesWithPut->has($variable->__toString()));
            $this->assertEqualsCanonicalizing(
                $variable,
                $variablesWithPut->get($variable->__toString())
            );
        }
    }
}
