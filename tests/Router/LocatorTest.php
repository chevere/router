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

namespace Chevere\Tests\Router;

use Chevere\Router\Locator;
use PHPUnit\Framework\TestCase;

final class LocatorTest extends TestCase
{
    public function testConstruct(): void
    {
        $repo = 'repo';
        $path = '/path';
        $locator = new Locator($repo, $path);
        $this->assertSame("${repo}:${path}", $locator->__toString());
        $this->assertSame($repo, $locator->repository());
        $this->assertSame($path, $locator->path());
    }
}
