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
use Chevere\Router\Locator;
use Chevere\Router\Path;
use Chevere\Router\Route;
use Chevere\Spec\Specs\GroupSpec;
use Chevere\Spec\Specs\RouteSpec;
use Chevere\Tests\Spec\_resources\src\TestController;
use PHPUnit\Framework\TestCase;

final class GroupSpecTest extends TestCase
{
    public function testConstruct(): void
    {
        $specDir = directoryForPath('/spec/');
        $repository = 'repo';
        $specGroupPathJson = $specDir
            ->getChild("${repository}/")
            ->path()
            ->__toString() . 'routes.json';
        $spec = new GroupSpec($specDir, $repository);
        $this->assertSame($specGroupPathJson, $spec->jsonPath());
        $this->assertSame(
            [
                'name' => $repository,
                'spec' => $specGroupPathJson,
                'routes' => [],
            ],
            $spec->toArray()
        );
    }

    public function testWithAddedRoutable(): void
    {
        $repository = 'repo';
        $routeLocator = new Locator($repository, '/path');
        $specDir = directoryForPath('/spec/');
        $repository = 'repo';
        $groupSpecDir = $specDir->getChild("${repository}/");
        $routesSpecPathJson = $groupSpecDir->path()->__toString() . 'routes.json';
        $route = (new Route('test', new Path('/route/path')))
            ->withAddedEndpoint(
                new Endpoint(new GetMethod(), new TestController())
            );
        $routableSpec = new RouteSpec(
            $groupSpecDir->getChild(ltrim($routeLocator->path(), '/') . '/'),
            $route,
            $repository
        );
        $spec = (new GroupSpec($specDir, $repository))
            ->withAddedRoutableSpec($routableSpec);
        $this->assertSame(
            [
                'name' => $repository,
                'spec' => $routesSpecPathJson,
                'routes' => [
                    $routableSpec->key() => $routableSpec->toArray(),
                ],
            ],
            $spec->toArray()
        );
    }
}
