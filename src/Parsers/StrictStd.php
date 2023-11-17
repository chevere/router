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

namespace Chevere\Router\Parsers;

use Chevere\Regex\Regex;
use FastRoute\RouteParser\Std;
use InvalidArgumentException;
use Throwable;
use function Chevere\Message\message;

/**
 * Strict version of `FastRoute\RouteParser\Std`, without optional routing.
 */
final class StrictStd extends Std
{
    /**
     * Matches:
     * - `/`
     * - `/file`
     * - `/folder/`
     * - `/{var}`
     * - `/{var:\d+}`
     * - `/folder/*`
     */
    public const REGEX_PATH = '#^\/$|^\/(?:[^\/]+\/)*[^\/]*$#';

    public function parse($route)
    {
        $matches = (new Regex(self::REGEX_PATH))->match($route);
        if ($matches === []) {
            throw new InvalidArgumentException(
                (string) message(
                    "Route `%provided%` doesn't match regex `%regex%`",
                    provided: $route,
                    regex: self::REGEX_PATH,
                )
            );
        }

        try {
            $datas = parent::parse($route);
        } catch (Throwable $e) { // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(
                previous: $e,
                message: (string) message(
                    'Unable to parse route `%route%`',
                    route: $route,
                )
            );
        }
        // @codeCoverageIgnoreEnd
        if (count($datas) > 1) {
            throw new InvalidArgumentException(
                (string) message(
                    'Optional routing at route `%route%` is forbidden',
                    route: $route,
                )
            );
        }

        return $datas;
    }
}
