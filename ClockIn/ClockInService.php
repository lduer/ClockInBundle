<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\ClockIn;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use App\Repository\TimesheetRepository;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;
use KimaiPlugin\ClockInBundle\Repository\LatestActivityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClockInService
{
    /**
     * @var TimesheetRepository
     */
    private $timesheetRepository;

    /**
     * @var ActivityRepository
     */
    private $activityRepository;

    /**
     * @var Activity
     */
    private $clockInActivity;

    /**
     * @var int
     */
    private $clockInActivityId;

    /**
     * Service constructor.
     *
     * @param TimesheetRepository $timesheetRepository
     * @param ActivityRepository $activityRepository
     * @param LatestActivityRepository $latestActivityRepository
     * @param ValidatorInterface $validator
     * @param TokenStorageInterface $tokenStorage
     * @param int $clockInActivityId
     */
    public function __construct(TimesheetRepository $timesheetRepository, ActivityRepository $activityRepository, int $clockInActivityId)
    {
        $this->timesheetRepository = $timesheetRepository;
        $this->activityRepository = $activityRepository;
        $this->clockInActivityId = $clockInActivityId;
    }

    /**
     * @return Activity|object|null
     * @throws ClockInException
     */
    public function getClockInActivity()
    {
        $this->clockInActivity = $this->activityRepository->find($this->clockInActivityId);

        if (null === $this->clockInActivity || 0 === $this->clockInActivityId) {
            throw new ClockInException(sprintf('The default clock in activity is not found. Did you configure it via parameter "%s"', 'clock-in.activity'));
        }

        return $this->clockInActivity;
    }

    /**
     * @return int
     */
    public function getClockInActivityId()
    {
        return $this->clockInActivityId;
    }

    public static function getButtonDisabledStates(LatestActivity $latestActivity = null)
    {

        if (null === $latestActivity) {
            return [
                'stop' => true,
                'start' => false,
                'pause' => true,
                'resume' => false
            ];
        }

        return [
            // active only when clocked in
            'stop' => (in_array($latestActivity->getAction(), [LatestActivity::ACTIVITY_STOP, LatestActivity::ACTIVITY_PAUSE])),
            // active when paused or stopped
            'start' => (in_array($latestActivity->getAction(), [null, LatestActivity::ACTIVITY_START, LatestActivity::ACTIVITY_RESUME])),
            // active only when clocked in.
            'pause' => (in_array($latestActivity->getAction(), [LatestActivity::ACTIVITY_PAUSE, LatestActivity::ACTIVITY_STOP])),
            // active only, when paused
            'resume' => (in_array($latestActivity->getAction(), [null, LatestActivity::ACTIVITY_RESUME, LatestActivity::ACTIVITY_STOP]))
        ];
    }
}
