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

use Chevere\Router\Tests\Helpers\ObjectHelper;
use Chevere\Router\Wildcard;
use Chevere\Router\WildcardMatch;
use Chevere\Router\Wildcards;
use Chevere\Throwable\Exceptions\OutOfRangeException;
use FastRoute\RouteParser\Std;
use PHPUnit\Framework\TestCase;

final class WildcardsTest extends TestCase
{
    public function testConstructEmpty(): void
    {
        $wildcards = new Wildcards();
        $this->assertCount(0, $wildcards);
    }

    public function testConstruct(): void
    {
        $name = 'test';
        $wildcard = new Wildcard(
            $name,
            new WildcardMatch(Std::DEFAULT_DISPATCH_REGEX)
        );
        $wildcards = (new Wildcards())->withPut($wildcard);
        $this->assertCount(1, $wildcards);
        $this->assertTrue($wildcards->has($name));
        $this->assertSame($wildcard, $wildcards->get($name));
        $this->expectException(OutOfRangeException::class);
        $wildcards->get('test2');
    }

    public function testClone(): void
    {
        $wildcards = new Wildcards();
        $clone = clone $wildcards;
        $this->assertNotSame($wildcards, $clone);
        $helper = new ObjectHelper($wildcards);
        $cloneHelper = new ObjectHelper($clone);
        foreach (['map'] as $property) {
            $this->assertNotSame(
                $helper->getPropertyValue($property),
                $cloneHelper->getPropertyValue($property)
            );
        }
    }

    public function testWithAddedWildcard(): void
    {
        $match = new WildcardMatch(Std::DEFAULT_DISPATCH_REGEX);
        $wildcards_array = [new Wildcard('test1', $match), new Wildcard('test2', $match)];
        $wildcards = new Wildcards();
        foreach ($wildcards_array as $wildcard) {
            $wildcardsWithPut = ($wildcardsWithPut ?? $wildcards)
                ->withPut($wildcard)
                ->withPut($wildcard);
            $this->assertNotSame($wildcards, $wildcardsWithPut);
        }
        $this->assertCount(2, $wildcardsWithPut);
        foreach ($wildcards_array as $pos => $wildcard) {
            $this->assertTrue($wildcardsWithPut->has($wildcard->__toString()));
            $this->assertEqualsCanonicalizing(
                $wildcard,
                $wildcardsWithPut->get($wildcard->__toString())
            );
        }
    }
}
