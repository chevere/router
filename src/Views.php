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
use function Chevere\Message\message;

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
                'Provided views directory does not exists'
            );
        }
        foreach ($this->routes as $name => $route) {
            $errors = [];
            foreach ($route->endpoints() as $endpoint) {
                $basename = $endpoint->bind()->view();
                if ($basename === '') {
                    continue;
                }
                $file = "{$dir}/{$basename}";
                if (! is_file($file)) {
                    $errors[] = (string) message(
                        <<<PLAIN
                        View `%baseName%` linked by route `%name%` at %caller% not found for view filepath in %file%
                        PLAIN,
                        baseName: $basename,
                        name: $name,
                        caller: $route->caller()->__toString(),
                        file: $file
                    );
                }
            }
            if ($errors !== []) {
                throw new LogicException(implode("\n", $errors));
            }
        }
    }
}
