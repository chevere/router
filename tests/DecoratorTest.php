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
use Chevere\Router\Wildcards;
use PHPUnit\Framework\TestCase;

final class DecoratorTest extends TestCase
{
    public function testConstructor(): void
    {
        $locator = new Locator('repo', '/path');
        $decorator = new Decorator($locator);
        $this->assertSame($locator, $decorator->locator());
        $this->assertCount(0, $decorator->wildcards());
    }

    public function testWithWildcard(): void
    {
        $wildcards = new Wildcards();
        $decorator = new Decorator(new Locator('repo', '/path'));
        $decoratorWithWildcards = $decorator
            ->withWildcards($wildcards);
        $this->assertNotSame($decorator, $decoratorWithWildcards);
        $this->assertEquals(
            $wildcards,
            $decoratorWithWildcards->wildcards()
        );
    }
}
