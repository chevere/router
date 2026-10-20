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

use Chevere\Router\Interfaces\IdentifierInterface;
use InvalidArgumentException;

final class Identifier implements IdentifierInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private string $group,
        private string $id
    ) {
        if (ctype_space($group)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid value provided for `%s` argument',
                    '$group'
                )
            );
        }
        if (ctype_space($id) || empty($id)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid value provided for `%s` argument',
                    '$id'
                )
            );
        }
    }

    public function group(): string
    {
        return $this->group;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'group' => $this->group,
            'name' => $this->id,
        ];
    }
}
