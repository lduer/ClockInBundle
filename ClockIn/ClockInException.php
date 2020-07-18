<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\ClockIn;

use KimaiPlugin\ClockInBundle\Entity\LatestActivity;

class ClockInException extends \Exception
{
    public static $error_msg = [
        LatestActivity::ACTIVITY_START => 'timesheet.is-running',
        LatestActivity::ACTIVITY_RESUME => 'timesheet.is-running',
        LatestActivity::ACTIVITY_STOP => 'timesheet.all-stopped',
        LatestActivity::ACTIVITY_PAUSE => 'timesheet.all-stopped',
    ];
}
