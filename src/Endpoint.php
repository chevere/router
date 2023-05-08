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

use Chevere\Common\Interfaces\DescribedInterface;
use Chevere\Common\Traits\DescribedTrait;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\EndpointInterface;

final class Endpoint implements EndpointInterface, DescribedInterface
{
    use DescribedTrait;

    private ?string $description;

    public function __construct(
        private MethodInterface $method,
        private BindInterface $bind
    ) {
        $this->description = $bind->controllerName()->__toString()::description();
        if ($this->description === '') {
            $this->description = $method->description();
        }
    }

    public function method(): MethodInterface
    {
        return $this->method;
    }

    public function bind(): BindInterface
    {
        return $this->bind;
    }
}
