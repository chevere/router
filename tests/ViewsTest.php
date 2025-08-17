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

use Chevere\Router\Views;
use Chevere\Tests\src\ControllerNoParameters;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\routes;

final class ViewsTest extends TestCase
{
    public function testDirNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            <<<PLAIN
            Argument `dir` provided is not a directory
            PLAIN
        );
        (new Views(routes()))
            ->assert(__DIR__ . '/not-found');
    }

    public function testAssert(): void
    {
        $this->expectNotToPerformAssertions();
        $routes = routes(
            a: route(
                '/a',
                GET: ControllerNoParameters::class
            ),
            b: route(
                '/b',
                view: basename(__FILE__),
                GET: ControllerNoParameters::class
            ),
            c: route(
                '/c',
                view: basename(__FILE__),
                GET: ControllerNoParameters::class
            ),
        );
        $views = new Views($routes);
        $views->assert(__DIR__);
    }

    public function testAssertFailure(): void
    {
        $this->expectException(LogicException::class);
        $mapNotFound = __DIR__ . '/not-found';
        $mapMissing = __DIR__ . '/missing';
        $mapNotThere = __DIR__ . '/not-there';
        $routeB = route(
            '/b',
            view: 'not-found',
            GET: ControllerNoParameters::class
        );
        $routeC = route(
            '/c',
            GET: bind('missing', ControllerNoParameters::class),
            PUT: ControllerNoParameters::class,
            POST: bind('not-there', ControllerNoParameters::class)
        );
        $routes = routes(
            a: route(
                '/a',
                GET: ControllerNoParameters::class,
            ),
            b: $routeB,
            c: $routeC
        );
        $this->expectExceptionMessage(
            <<<PLAIN
            View `not-found` linked by route `/b` at {$routeB->caller()} not found for view mapped path at {$mapNotFound}
            View `missing` linked by route `/c` at {$routeC->caller()} not found for view mapped path at {$mapMissing}
            View `not-there` linked by route `/c` at {$routeC->caller()} not found for view mapped path at {$mapNotThere}
            PLAIN
        );
        $views = new Views($routes);
        $views->assert(__DIR__);
    }
}
