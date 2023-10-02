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

use Chevere\Attributes\Description;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Router\Interfaces\BindInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use ReflectionClass;
use function Chevere\Attribute\getAttribute;

final class Endpoint implements EndpointInterface
{
    private string $description;

    public function __construct(
        private MethodInterface $method,
        private BindInterface $bind
    ) {
        $reflection = new ReflectionClass($this->bind->controllerName()->__toString());
        /** @var Description $description */
        $description = getAttribute($reflection, Description::class);
        $this->description = strval($description);
        if ($this->description === '') {
            $this->description = $method->description();
        }
    }

    public function description(): string
    {
        return $this->description;
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
