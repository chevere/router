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

use Chevere\Message\Message;
use Chevere\Regex\Interfaces\RegexInterface;
use Chevere\Regex\Regex;
use Chevere\Router\Interfaces\PathInterface;
use Chevere\Router\Interfaces\WildcardsInterface;
use Chevere\Router\Parsers\StrictStd;
use Chevere\Throwable\Exceptions\LogicException;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use Throwable;

final class Path implements PathInterface
{
    /**
     * string|array for mixed types.
     * @var array<int, mixed>
     */
    private array $data;

    private RegexInterface $regex;

    private WildcardsInterface $wildcards;

    private string $handle;

    public function __construct(
        private string $route
    ) {
        $std = new StrictStd();
        $this->data = $std->parse($this->route)[0];
        $dataGenerator = new DataGenerator();

        try {
            $dataGenerator->addRoute('GET', $this->data, '');
        } catch (Throwable $e) { // @codeCoverageIgnoreStart
            throw new LogicException(
                previous: $e,
                message: (new Message('Unable to add route %path%'))
                    ->withCode('%path%', $this->route),
            );
        }
        // @codeCoverageIgnoreEnd
        $this->setHandle();
        $this->wildcards = new Wildcards();
        $routerData = array_values(array_filter($dataGenerator->getData()));
        foreach ($this->data as $value) {
            if (! is_array($value)) {
                continue;
            }
            $this->wildcards = $this->wildcards
                ->withPut(
                    new Wildcard($value[0], new WildcardMatch($value[1]))
                );
        }
        $this->regex = new Regex(
            $routerData[0]['GET'][0]['regex'] ?? ('#' . $route . '#') // @phpstan-ignore-line
        );
    }

    public function __toString(): string
    {
        return $this->route;
    }

    public function wildcards(): WildcardsInterface
    {
        return $this->wildcards;
    }

    public function regex(): RegexInterface
    {
        return $this->regex;
    }

    public function handle(): string
    {
        return $this->handle;
    }

    private function setHandle(): void
    {
        $this->handle = '';
        /**
         * @var string|string[] $el
         */
        foreach ($this->data as $el) {
            $this->handle .= is_string($el)
                ? $el
                : '{' . $el[0] . '}';
        }
    }
}
