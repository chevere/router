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

namespace Chevere\Tests\Spec\Specs;

use function Chevere\Filesystem\directoryForPath;
use Chevere\Http\Methods\GetMethod;
use Chevere\Router\Endpoint;
use Chevere\Router\Path;
use Chevere\Router\Route;
use Chevere\Spec\Specs\GroupSpec;
use Chevere\Spec\Specs\IndexSpec;
use Chevere\Tests\Spec\_resources\src\TestController;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;

final class IndexSpecTest extends TestCase
{
    public function testConstruct(): void
    {
        $spec = new IndexSpec(directoryForPath('/spec/'));
        $this->assertSame([
            'repositories' => [],
        ], $spec->toArray());
    }

    public function testWithAddedGroup(): void
    {
        $routePath = new Path('/route/path');
        $specDir = directoryForPath('/spec/');
        $repository = 'repo';
        $route = (new Route('test', $routePath))
            ->withAddedEndpoint(
                new Endpoint(new GetMethod(), new TestController())
            );
        $objectStorage = new SplObjectStorage();
        $objectStorage->attach($route);
        $groupSpec = new GroupSpec($specDir, $repository);
        $spec = (new IndexSpec($specDir))->withAddedGroup($groupSpec);
        $this->assertSame(
            $specDir->path()->__toString() . 'index.json',
            $spec->jsonPath()
        );
        $this->assertSame(
            [
                'repositories' => [
                    $groupSpec->key() => $groupSpec->toArray(),
                ],
            ],
            $spec->toArray()
        );
    }
}
