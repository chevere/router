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
                sprintf(
                    "Route `%s` doesn't match regex `%s`",
                    $route,
                    self::REGEX_PATH,
                )
            );
        }

        try {
            $datas = parent::parse($route);
        } catch (Throwable $e) { // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(
                previous: $e,
                message: sprintf(
                    'Unable to parse route `%s`',
                    $route,
                )
            );
        }
        // @codeCoverageIgnoreEnd
        if (count($datas) > 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Optional routing at route `%s` is forbidden',
                    $route,
                )
            );
        }

        return $datas;
    }
}
