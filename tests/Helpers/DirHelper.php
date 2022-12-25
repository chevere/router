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

namespace Chevere\Tests\Helpers;

use Chevere\Filesystem\Directory;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Chevere\Filesystem\Path;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

final class DirHelper
{
    private DirectoryInterface $dir;

    public function __construct(TestCase $object)
    {
        $reflection = new ReflectionObject($object);
        $dir = dirname($reflection->getFileName());
        $shortName = $reflection->getShortName();
        $this->dir = new Directory(new Path("${dir}/_resources/${shortName}/"));
    }

    public function dir(): DirectoryInterface
    {
        return $this->dir;
    }
}
