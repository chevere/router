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

namespace Chevere\Tests\Spec\_resources\src;

use Chevere\Controller\Controller;
use Chevere\Parameter\Attributes\ParameterAttribute;

class SpecMakerTestGetController extends Controller
{
    public function run(
        #[ParameterAttribute('The user integer id', '/^[0-9]+$/')]
        string $id,
        #[ParameterAttribute('The user name', '/^[\w]+$/')]
        string $name = 'default'
    ): array {
        return [];
    }
}
