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
use Chevere\Controller\Interfaces\HttpControllerInterface;
use Chevere\Http\Interfaces\MethodInterface;
use Chevere\Message\Message;
use Chevere\Parameter\Interfaces\StringParameterInterface;
use Chevere\Router\Interfaces\EndpointInterface;
use Chevere\Throwable\Exceptions\OutOfBoundsException;

final class Endpoint implements EndpointInterface
{
    use DescriptionTrait;

    private string $description = '';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $parameters = [];

    public function __construct(
        private MethodInterface $method,
        private HttpControllerInterface $httpController
    ) {
        $this->description = $httpController->getDescription();
        if ($this->description === '') {
            $this->description = $method->description();
        }
        /**
         * @var StringParameterInterface $parameter
         */
        foreach ($httpController->parameters()->getIterator() as $name => $parameter) {
            $this->parameters[$name] = [
                'name' => $name,
                'regex' => $parameter->regex()->__toString(),
                'description' => $parameter->description(),
                'isRequired' => $httpController->parameters()->isRequired($name),
            ];
        }
    }

    public function method(): MethodInterface
    {
        return $this->method;
    }

    public function httpController(): HttpControllerInterface
    {
        return $this->httpController;
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
