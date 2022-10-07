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

class TestController extends Controller
{
    public function run(
        #[ParameterAttribute(regex: '/^[\w]+$/')]
        string $name,
        #[ParameterAttribute(regex: '/^[0-9]+$/')]
        string $id
    ): array {
        return [
            'userName' => $name,
            'userId' => $id,
        ];
    }
}
