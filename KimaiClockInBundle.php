<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KimaiClockInBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
