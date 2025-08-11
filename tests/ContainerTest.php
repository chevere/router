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

use Chevere\Router\Container;
use Chevere\Router\Dependencies;
use Chevere\Router\Exceptions\ContainerException;
use Chevere\Router\Exceptions\ContainerNotFoundException;
use Chevere\Router\Path;
use Chevere\Tests\src\ControllerWithDependencies;
use Chevere\Tests\src\Dependency;
use Chevere\Tests\src\MiddlewareWithDependencies;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use function Chevere\Router\headless;
use function Chevere\Router\route;
use function Chevere\Router\routes;

final class ContainerTest extends TestCase
{
    public function testGet(): void
    {
        $container = new Container(foo: 'bar');
        $this->assertSame('bar', $container->get('foo'));
        $this->expectException(ContainerNotFoundException::class);
        $container->get('baz');
    }

    public function testHas(): void
    {
        $container = new Container(foo: 'bar');
        $this->assertTrue($container->has('foo'));
        $this->assertFalse($container->has('baz'));
    }

    public function testWith(): void
    {
        $container = new Container(foo: 'bar');
        $path = new Path('/');
        $newContainer = $container->with(foo: $path);
        $this->assertNotSame($container, $newContainer);
        $this->assertTrue($newContainer->has('foo'));
        $this->assertSame($path, $newContainer->get('foo'));
    }

    public function testWithout(): void
    {
        $container = new Container(foo: 'bar');
        $this->assertTrue($container->has('foo'));
        $newContainer = $container->without('foo');
        $this->assertNotSame($container, $newContainer);
        $this->assertFalse($newContainer->has('foo'));
    }

    public function testWithAutoInject(): void
    {
        $routes = routes(
            route(
                '/{id}',
                GET: headless(
                    ControllerWithDependencies::class,
                    MiddlewareWithDependencies::class,
                )
            ),
        );
        $dependencies = new Dependencies($routes);
        $ignore = ['int', 'isDev'];
        $path = new Path('/');
        $container = new Container(
            dependency: $path,
        );
        $newContainer = $container->withAutoInject($dependencies, ...$ignore);
        $this->assertNotSame(
            spl_object_id($container),
            spl_object_id($newContainer)
        );
        $this->assertNotSame($container, $newContainer);
        $this->assertFalse($newContainer->has('int'));
        $this->assertTrue($newContainer->has('dependency'));
        $this->assertTrue($newContainer->has('datetime'));
        $this->assertSame(
            $path,
            $newContainer->get('dependency')
        );
        $this->assertInstanceOf(
            DateTimeInterface::class,
            $newContainer->get('datetime')
        );
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            <<<PLAIN
            [int]: Parameter int is not an object type
            [isDev]: Parameter isDev is not an object type
            PLAIN
        );
        $container->withAutoInject($dependencies);
    }

    public function testWithAutoInjectMissingNested(): void
    {
        $routes = routes(
            route(
                '/{id}',
                GET: headless(
                    ControllerWithDependencies::class,
                )
            ),
        );
        $dependencies = new Dependencies($routes);
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            <<<PLAIN
            [dependency]: Failed to resolve dependencies for Chevere\Router\Path: Missing required argument(s): `route`
            PLAIN
        );
        (new Container())->withAutoInject($dependencies);
    }

    public function testExtract(): void
    {
        $container = new Container();
        $this->assertInstanceOf(
            stdClass::class,
            $container->extract(Dependency::class)['stdClass']
        );
    }
}
