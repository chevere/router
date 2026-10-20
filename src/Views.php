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

namespace Chevere\Router;

use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Router\Interfaces\ViewsInterface;
use InvalidArgumentException;
use LogicException;

final class Views implements ViewsInterface
{
    public function __construct(
        private RoutesInterface $routes
    ) {
    }

    public function assert(string $dir): void
    {
        if (! is_dir($dir)) {
            throw new InvalidArgumentException(
                <<<PLAIN
                Argument `dir` provided is not a directory
                PLAIN
            );
        }
        $errors = [];
        foreach ($this->routes as $name => $route) {
            foreach ($route->endpoints() as $endpoint) {
                $basename = $endpoint->bind()
                    ->view();
                if ($basename === '') {
                    continue;
                }
                $file = "{$dir}/{$basename}";
                if (! is_file($file)) {
                    $errors[] = sprintf(
                        <<<PLAIN
                        View `%s` linked by route `%s` at %s not found for view mapped path at %s
                        PLAIN
                        ,
                        $basename,
                        $name,
                        $route->caller()
                            ->__toString(),
                        $file
                    );
                }
            }
        }
        if ($errors !== []) {
            throw new LogicException(implode("\n", $errors));
        }
    }
}
