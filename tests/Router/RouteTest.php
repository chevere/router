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

use Chevere\Controller\Controller;
use Chevere\Http\Methods\GetMethod;
use Chevere\Http\Methods\PostMethod;
use Chevere\Parameter\Attributes\ParameterAttribute;
use Chevere\Router\Endpoint;
use Chevere\Router\Exceptions\EndpointConflictException;
use Chevere\Router\Exceptions\WildcardConflictException;
use Chevere\Router\Path;
use function Chevere\Router\route;
use Chevere\Router\Route;
use Chevere\Throwable\Exceptions\InvalidArgumentException;
use Chevere\Throwable\Exceptions\OutOfBoundsException;
use Chevere\Throwable\Exceptions\OverflowException;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testConstruct(): void
    {
        $path = '/test';
        $routePath = new Path($path);
        $route = new Route('test', $routePath);
        $this->assertSame($routePath, $route->path());
        $this->assertSame('', $route->view());
        $route = route($path, 'name', 'view');
        $this->assertSame('name', $route->name());
        $this->assertEquals($routePath, $route->path());
        $this->assertSame('view', $route->view());
    }

    public function testWithAddedEndpoint(): void
    {
        $route = new Route('test', new Path('/test'));
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, $controller);
        $route = $route->withAddedEndpoint($endpoint);
        $this->assertTrue($route->endpoints()->hasKey($method->name()));
        $this->assertSame($endpoint, $route->endpoints()->get($method->name()));
    }

    public function testWithAddedEndpointWrongWildcard(): void
    {
        $route = new Route('test', new Path('/test/{foo}'));
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, $controller);
        $this->expectException(OutOfBoundsException::class);
        $route->withAddedEndpoint($endpoint);
    }

    public function testWithAddedEndpointNoParams(): void
    {
        $route = new Route('test', new Path('/test/{foo}'));
        $method = new GetMethod();
        $controller = new RouteTestControllerNoParams();
        $endpoint = new Endpoint($method, $controller);
        $this->expectException(InvalidArgumentException::class);
        $route->withAddedEndpoint($endpoint);
    }

    public function testWithAddedEndpointWildcardParameter(): void
    {
        $route = new Route('test', new Path('/test/{id:[0-9]+}'));
        $method = new GetMethod();
        $controller = new RouteTestController();
        $endpoint = new Endpoint($method, $controller);
        $route = $route->withAddedEndpoint($endpoint);
        $this->assertTrue($route->endpoints()->hasKey($method->name()));
        $this->assertSame(
            [],
            $route->endpoints()->get($method->name())->parameters()
        );
    }

    public function testWithAddedEndpointOverride(): void
    {
        $route = new Route('test', new Path('/test/{id:[0-9]+}'));
        $endpoint = new Endpoint(new GetMethod(), new RouteTestController());
        $route = $route->withAddedEndpoint($endpoint);
        $this->expectException(OverflowException::class);
        $route->withAddedEndpoint($endpoint);
    }

    public function testWithAddedEndpointConflict(): void
    {
        $route = new Route('test', new Path('/test/{id:[0-9]+}'));
        $endpoint1 = new Endpoint(new GetMethod(), new RouteTestController());
        $endpoint2 = new Endpoint(new PostMethod(), new RouteTestControllerRegexConflict());
        $route = $route->withAddedEndpoint($endpoint1);
        $this->expectException(EndpointConflictException::class);
        $route->withAddedEndpoint($endpoint2);
    }

    public function testWithAddedEndpointWildcardConflict(): void
    {
        $route = new Route('test', new Path('/test/{id:\w+}'));
        $endpoint = new Endpoint(new GetMethod(), new RouteTestController());
        $this->expectException(WildcardConflictException::class);
        $route->withAddedEndpoint($endpoint);
    }
}

final class RouteTestController extends Controller
{
    public function run(
        #[ParameterAttribute(regex: '/^[0-9]+$/')]
        string $id
    ): array {
        return [];
    }
}

final class RouteTestControllerNoParams extends Controller
{
    public function run(): array
    {
        return [];
    }
}

final class RouteTestControllerRegexConflict extends Controller
{
    public function run(
        #[ParameterAttribute(regex: '/^\W+$/')]
        string $id
    ): array {
        return [];
    }
}
