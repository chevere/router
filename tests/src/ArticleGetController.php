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

namespace Chevere\Tests\src;

use Chevere\Http\Attributes\Description;
use Chevere\Http\Controller;
use Chevere\Parameter\Attributes\StringAttr;

#[Description('Endpoint description')]
final class ArticleGetController extends Controller
{
    protected function main(
        #[StringAttr('/\d+/')]
        string $id
    ): array {
        return [];
    }
}
