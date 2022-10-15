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
use Chevere\Spec\Specs\RouteEndpointSpec;
use Chevere\Spec\Specs\RouteSpec;
use Chevere\Tests\Spec\_resources\src\TestController;
use PHPUnit\Framework\TestCase;

final class RouteSpecTest extends TestCase
{
    public function testConstruct(): void
    {
        $repository = 'repo';
        $routeLocator = new Locator($repository, '/route/path');
        $routePath = new Path('/route/path');
        $specDir = directoryForPath("/spec/${repository}/");
        $routeSpecPath = $specDir
            ->getChild(ltrim($routeLocator->path(), '/') . '/')
            ->path()
            ->__toString() . 'route.json';
        $method = new GetMethod();
        $routeEndpoint = (new Endpoint($method, new TestController()))
            ->withDescription('Test endpoint');
        $route = (new Route('test', $routePath))
            ->withAddedEndpoint($routeEndpoint);
        $spec = new RouteSpec($specDir, $route, $repository);
        $routeEndpoint = new RouteEndpointSpec(
            $specDir->getChild(ltrim($routeLocator->path(), '/') . '/'),
            $routeEndpoint
        );
        $this->assertSame($routeSpecPath, $spec->jsonPath());
        $this->assertSame(
            [
                'name' => $routePath->name(),
                'locator' => $routeLocator->__toString(),
                'spec' => $routeSpecPath,
                'regex' => $routePath->regex()->__toString(),
                'wildcards' => $routePath->wildcards()->toArray(),
                'endpoints' => [
                    $method->name() => $routeEndpoint->toArray(),
                ],
            ],
            $spec->toArray()
        );
    }
}
