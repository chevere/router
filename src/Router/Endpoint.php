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

use Chevere\Common\Traits\DescriptionTrait;
use Chevere\Controller\Interfaces\ControllerInterface;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;

final class Endpoint implements EndpointInterface
{
    use DescriptionTrait;

    private string $description = '';

    private array $parameters = [];

    public function __construct(
        private MethodInterface $method,
        private ControllerInterface $controller
    ) {
        $this->description = $controller->getDescription();
        if ($this->description === '') {
            $this->description = $method->description();
        }
        /**
         * @var StringParameterInterface $parameter
         */
        foreach ($controller->parameters()->getIterator() as $name => $parameter) {
            $array = [
                'name' => $name,
                'regex' => $parameter->regex()->__toString(),
                'description' => $parameter->description(),
                'isRequired' => $controller->parameters()->isRequired($name),
            ];
            $this->parameters[$name] = $array;
        }
    }

    public function method(): MethodInterface
    {
        return $this->method;
    }

    public function controller(): ControllerInterface
    {
        return $this->controller;
    }

    public function withDescription(string $description): static
    {
        $new = clone $this;
        $new->description = $description;

        return $new;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function withoutParameter(string $parameter): EndpointInterface
    {
        if (! array_key_exists($parameter, $this->parameters)) {
            throw new OutOfBoundsException(
                (new Message("Parameter %parameter% doesn't exists"))
                    ->withCode('%parameter%', $parameter)
            );
        }
        $new = clone $this;
        unset($new->parameters[$parameter]);

        return $new;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
