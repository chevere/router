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

namespace Chevere\Tests\Spec;

use function Chevere\Filesystem\directoryForPath;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Chevere\Filesystem\Interfaces\PathInterface;
use Chevere\Http\Methods\GetMethod;
use Chevere\Http\Methods\PutMethod;
use Chevere\Router\Endpoint;
use Chevere\Router\Path;
use Chevere\Router\Route;
use Chevere\Router\Router;
use Chevere\Spec\SpecMaker;
use Chevere\Tests\Spec\_resources\src\SpecMakerTestGetController;
use Chevere\Tests\Spec\_resources\src\SpecMakerTestPutController;
use Chevere\Tests\src\DirHelper;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SpecMakerTest extends TestCase
{
    private DirHelper $dirHelper;

    private DirectoryInterface $buildDir;

    protected function setUp(): void
    {
        $this->dirHelper = new DirHelper($this);
        $this->buildDir = $this->dirHelper->dir()->getChild('build/');
    }

    public function testConstructInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SpecMaker(
            directoryForPath('/spec/'),
            $this->buildDir->getChild('spec/'),
            new Router()
        );
    }

    public function testBuild(): void
    {
        $putMethod = new PutMethod();
        $getMethod = new GetMethod();
        $route = new Route(
            'test',
            new Path('/route-path/{id:[0-9]+}')
        );
        $route = $route
            ->withAddedEndpoint(
                new Endpoint($putMethod, new SpecMakerTestPutController())
            )
            ->withAddedEndpoint(
                new Endpoint($getMethod, new SpecMakerTestGetController())
            );
        $router = (new Router())
            ->withAddedRoute(group: 'repo', route: $route);
        $specMaker = new SpecMaker(
            directoryForPath('/spec/'),
            $this->buildDir->getChild('spec/'),
            $router
        );
        $buildPath = $this->buildDir->path();
        /**
         * @var PathInterface $path
         */
        foreach ($specMaker->files() as $jsonPath => $path) {
            $cachedFile = $buildPath->getChild(ltrim($jsonPath, '/'))->__toString();
            $this->assertFileEquals(
                $cachedFile,
                $path->__toString(),
                $cachedFile
            );
        }
        $this->assertTrue($specMaker->specIndex()->has(
            $route->path()->__toString(),
            $putMethod->name()
        ));
        $this->assertTrue($specMaker->specIndex()->has(
            $route->path()->__toString(),
            $getMethod->name()
        ));
    }
}
