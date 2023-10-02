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

namespace Chevere\Router\Interfaces;

use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Http\Methods\ConnectMethod;
use Chevere\Http\Methods\DeleteMethod;
use Chevere\Http\Methods\GetMethod;
use Chevere\Http\Methods\HeadMethod;
use Chevere\Http\Methods\OptionsMethod;
use Chevere\Http\Methods\PatchMethod;
use Chevere\Http\Methods\PostMethod;
use Chevere\Http\Methods\PutMethod;
use Chevere\Http\Methods\TraceMethod;

/**
 * Describes the component in charge of defining a route endpoint.
 *
 * Note: Parameters must be automatically determined from known `$controller` parameters.
 */
interface EndpointInterface
{
    /**
     * Known HTTP methods
     */
    public const KNOWN_METHODS = [
        'CONNECT' => ConnectMethod::class,
        'DELETE' => DeleteMethod::class,
        'GET' => GetMethod::class,
        'HEAD' => HeadMethod::class,
        'OPTIONS' => OptionsMethod::class,
        'PATCH' => PatchMethod::class,
        'POST' => PostMethod::class,
        'PUT' => PutMethod::class,
        'TRACE' => TraceMethod::class,
    ];

    public function method(): MethodInterface;

    public function bind(): BindInterface;

    public function description(): string;
}
