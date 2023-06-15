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

use Chevere\Router\Decorator;
use Chevere\Router\Locator;
use Chevere\Router\Variables;
use PHPUnit\Framework\TestCase;

final class DecoratorTest extends TestCase
{
    public function testConstructor(): void
    {
        $locator = new Locator('repo', '/path');
        $decorator = new Decorator($locator);
        $this->assertSame($locator, $decorator->locator());
        $this->assertCount(0, $decorator->variables());
    }

    public function testWithVariables(): void
    {
        $variables = new Variables();
        $decorator = new Decorator(new Locator('repo', '/path'));
        $decoratorWithVariables = $decorator
            ->withVariables($variables);
        $this->assertNotSame($decorator, $decoratorWithVariables);
        $this->assertEquals(
            $variables,
            $decoratorWithVariables->variables()
        );
    }
}
