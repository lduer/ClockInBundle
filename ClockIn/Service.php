<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\ClockIn;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\TimesheetRepository;
//use Doctrine\Persistence\ObjectManager;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;
use KimaiPlugin\ClockInBundle\Repository\LatestActivityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Service
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
     * @var LatestActivityRepository
     */
    private $latestActivityRepository;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var User
     */
    private $user;

    /**
     * @var LatestActivity
     */
    private $latestActivity;

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
    public function __construct(TimesheetRepository $timesheetRepository, ActivityRepository $activityRepository, LatestActivityRepository $latestActivityRepository, ValidatorInterface $validator, TokenStorageInterface $tokenStorage, int $clockInActivityId)
    {
        $this->timesheetRepository = $timesheetRepository;
        $this->activityRepository = $activityRepository;
        $this->latestActivityRepository = $latestActivityRepository; //$this->objectManager->getRepository(LatestActivity::class);
        $this->validator = $validator;
        $this->clockInActivityId = $clockInActivityId;

        $this->setTokenStorage($tokenStorage);
    }

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        if (null !== $tokenStorage->getToken()) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
    }

    /**
     * @return Activity|object|null
     * @throws ClockInException
     */
    private function getClockInActivity()
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

    /**
     * @param null $user
     * @return User|object|string|null
     */
    private function getUser($user = null)
    {
        if (null === $user) {
            $user = $this->user;
        }

        return $user;
    }

    /**
     * @param User|null $user
     * @return LatestActivity|null
     */
    public function findLatestActivity(User $user = null)
    {
        $user = $this->getUser($user);
        if (null === $this->latestActivity) {
            $this->latestActivity = $this->latestActivityRepository->getLatestActivity($user);
        }

        return $this->latestActivity;
    }

    /**
     * @param LatestActivity $latestActivity
     * @return $this
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateLatestActivity(LatestActivity $latestActivity)
    {
        $this->latestActivityRepository->updateLatestActivity($latestActivity);

        return $this;
    }

    /**
     * @param $latestActivity
     * @return $this
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeLatestActivity($latestActivity)
    {
        $this->latestActivityRepository->updateLatestActivity($latestActivity);

        return $this;
    }

    /**
     * @param User|null $user
     * @return Timesheet|null
     */
    public function findLatestActivityTimesheet(User $user = null)
    {
        $user = $this->getUser($user);

        if (null !== $latestActivity = $this->findLatestActivity($user)) {
            return $latestActivity->getTimesheet();
        }

        return null;
    }

    /**
     * @param User $user
     * @param Timesheet $timesheet
     * @param null $action
     * @return LatestActivity|null
     * @throws \Doctrine\ORM\ORMException
     */
    public function manageLatestActivity(User $user, Timesheet $timesheet, $action = null)
    {
        $latestActivity = $this->findLatestActivity($user);

        if (null === $latestActivity) {
            $latestActivity = new LatestActivity($timesheet, $action, $user);

            $latestActivity
                ->setAction($action)
                ->setTimesheet($timesheet);
        }

        $latestActivity
            ->setTimesheet($timesheet)
            ->setAction($action);

        $this->latestActivityRepository->save($latestActivity);

        return $latestActivity;
    }

    /**
     * @param User|null $user
     * @return Timesheet|int
     * @throws ClockInException
     */
    public function start(User $user = null)
    {
        return $this->startAction(LatestActivity::ACTIVITY_START, $user);
    }

    /**
     * @param User|null $user
     * @return Timesheet|int
     * @throws ClockInException
     */
    public function resume(User $user = null)
    {
        return $this->startAction(LatestActivity::ACTIVITY_RESUME, $user);
    }

    /**
     * @param User|null $user
     * @return Timesheet|int|null
     */
    public function stop(User $user = null)
    {
        return $this->stopAction(LatestActivity::ACTIVITY_STOP, $user);
    }

    /**
     * @param User|null $user
     * @return Timesheet|int|null
     */
    public function pause(User $user = null)
    {
        return $this->stopAction(LatestActivity::ACTIVITY_PAUSE, $user);
    }

    /**
     * @param $action
     * @param User|null $user
     * @return Timesheet|null
     * @throws ClockInException
     */
    private function startAction($action, User $user = null)
    {
        if (!in_array($action, [LatestActivity::ACTIVITY_START, LatestActivity::ACTIVITY_RESUME])) {
            throw new InvalidArgumentException(sprintf(
                '(system misconfiguration) Method argument $activity must be one of %s',
                implode(', ', [LatestActivity::ACTIVITY_START, LatestActivity::ACTIVITY_RESUME])
            ));
        }

        $user = $this->getUser($user);
        $activeEntries = $this->timesheetRepository->getActiveEntries($user);

        if (($activeEntriesCount = count($activeEntries)) !== 0) {
//            TODO
            throw new ClockInException('More than 0 started activities');
        }

        // find latestActivity and use timesheet-informations
        $latestActivityTimesheet = $this->findLatestActivityTimesheet($user);

        if (null !== $latestActivityTimesheet && $action === LatestActivity::ACTIVITY_RESUME) {
            $entry = clone $latestActivityTimesheet;

            $entry->setUser($this->getUser());
            $entry->setBegin(new \DateTime());
            $entry->setEnd(null);
        }

        if (null === $latestActivityTimesheet || $action === LatestActivity::ACTIVITY_START) {
            // default entry for "start" activity:
            $clockInActivity = $this->getClockInActivity();

            $entry = new Timesheet();
            $entry
                ->setBegin(new \DateTime())
                ->setUser($user)
                ->setActivity($clockInActivity);

            $entry->setProject($clockInActivity->getProject());
        }

        $this->timesheetRepository->save($entry);

        $this->manageLatestActivity($user, $entry, $action);

        return $entry;
    }

    /**
     * @param $action
     * @param User|null $user
     * @param bool $manageLatestActivity
     * @return Timesheet|int|null
     */
    private function stopAction($action, User $user = null, bool $manageLatestActivity = true)
    {
        $user = $this->getUser($user);

        // stop ALL timerecords: get all active timerecords and set end to "now"
        $activeEntries = $this->timesheetRepository->getActiveEntries($user);

        if (count($activeEntries) === 0) {
            // all entries are already stopped
            return null;
        }

        // fallback, if more than one records are active
        // use latest of all entries for managing the latest activity
        $timesheet = null;
        $activeEntries = array_reverse($activeEntries);
        foreach ($activeEntries as $currentEntry) {
            $timesheet = $currentEntry;
            $this->timesheetRepository->stopRecording($currentEntry);
        }

        // when called from inside other method, this doesn't need to be called
        if ($manageLatestActivity) {
            $this->manageLatestActivity($user, $timesheet, $action);
        }

        return $timesheet;
    }

    /**
     * @param Timesheet $timesheet
     * @param User|null $user
     * @return Timesheet
     * @throws \Exception
     */
    public function startTimesheet(Timesheet $timesheet, User $user = null)
    {
        $user = $this->getUser($user);

        // stop all previous entries
        $this->stopAction(LatestActivity::ACTIVITY_STOP, $user, false);

        $entry = clone $timesheet;
        $entry->setUser($user);
        $entry->setBegin(new \DateTime());
        $entry->setEnd(null);

        // is this needed? create action should be done in other functions
        $this->timesheetRepository->save($entry);

        $this->manageLatestActivity($user, $entry);

        return $entry;
    }
}
