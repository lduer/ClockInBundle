<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\ClockIn;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use LDuer\KimaiClockInBundle\Entity\LatestActivity;
use LDuer\KimaiClockInBundle\Repository\LatestActivityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Service
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var TimesheetRepository
     */
    private $timesheetRepository;

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
     * TODO create exception class ClockInException and handle it in controller (instead of int-values) - translate error messages for frontend
     *
     * @param ObjectManager $objectManager
     * @param TimesheetRepository $timesheetRepository
     * @param ValidatorInterface $validator
     * @param TokenStorageInterface $tokenStorage
     * @param int $clockInActivityId
     */
    public function __construct(ObjectManager $objectManager, TimesheetRepository $timesheetRepository, ValidatorInterface $validator, TokenStorageInterface $tokenStorage, int $clockInActivityId)
    {
        $this->objectManager = $objectManager;
        $this->timesheetRepository = $timesheetRepository;
        $this->validator = $validator;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->clockInActivityId = $clockInActivityId;
    }

    /**
     * @return Activity|null
     */
    private function getClockInActivity()
    {
        if (null === $this->clockInActivity) {
            $this->clockInActivity = $this->objectManager->getRepository(Activity::class)->find($this->clockInActivityId);
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
     * @return LatestActivity|LatestActivity[]|null
     */
    public function findLatestActivity(User $user = null)
    {
        $user = $this->getUser($user);
        if (null === $this->latestActivity) {
            /** @var LatestActivityRepository $repository */
            $repository = $this->objectManager->getRepository(LatestActivity::class);
            $this->latestActivity = $repository->getLatestActivity($user);
        }

        return $this->latestActivity;
    }

    /**
     * @param User $user
     * @param Timesheet $timesheet
     * @param null $action
     * @return LatestActivity|LatestActivity[]|null
     */
    protected function manageLatestActivity(User $user, Timesheet $timesheet, $action = null)
    {
        $latestActivity = $this->findLatestActivity($user);

        if (null === $latestActivity) {
            $latestActivity = new LatestActivity($timesheet, $action, $user);

            $this->objectManager->persist($latestActivity);
        } else {
            $latestActivity
                ->setAction($action)
                ->setTimesheet($timesheet);
        }

        // set $action to latest activity
        $latestActivity
            ->setTimesheet($timesheet)
            ->setAction($action);

        return $latestActivity;
    }

    /**
     * @param User|null $user
     * @return Timesheet|int
     * @throws \Exception
     */
    public function start(User $user = null)
    {
        return $this->startAction(LatestActivity::ACTIVITY_START, $user);
    }

    /**
     * @param User|null $user
     * @return Timesheet|int
     * @throws \Exception
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
     * @return Timesheet|int
     * @throws \Exception
     */
    private function startAction($action, User $user = null)
    {
        $user = $this->getUser($user);

        // check if no timesheets are started
        $activeEntries = $this->timesheetRepository->getActiveEntries($user);

        if (($activeEntriesCount = count($activeEntries)) !== 0) {
            // break action and return number of active entries
            return $activeEntriesCount;
        }

        // find latestActivity and use timesheet-informations
        $latestActivity = $this->findLatestActivity($user);

        if ($action === LatestActivity::ACTIVITY_RESUME) {
            $entry = clone $latestActivity->getTimesheet();

            $entry->setUser($this->getUser());
            $entry->setBegin(new \DateTime());
            $entry->setEnd(null);
        } elseif ($action === LatestActivity::ACTIVITY_START) {
            // default entry for "start" activity:
            $clockInActivity = $this->getClockInActivity();

            $entry = new Timesheet();
            $entry
                ->setBegin(new \DateTime())
                ->setUser($user)
                ->setActivity($clockInActivity);

            if (null === ($project = $clockInActivity->getProject())) {
                throw new InvalidArgumentException('(system misconfiguration) The default activity for clock-in times does not have a project selected');
            } else {
                $entry->setProject($project);
            }
        } else {
            throw new InvalidArgumentException(sprintf(
                '(system misconfiguration) Method argument $activity must be one of %s',
                implode(', ', [LatestActivity::ACTIVITY_START, LatestActivity::ACTIVITY_RESUME])
            ));
        }

//        $errors = $this->validator->validate($entry);
//        if (count($errors) > 0) {
//        throw new InvalidArgumentException($errors[0]->getPropertyPath() . ' = ' . $errors[0]->getMessage());

        /* @var EntityManagerInterface $entityManager */
        $this->objectManager->persist($entry);

        $this->manageLatestActivity($user, $entry, $action);
        $this->objectManager->flush();

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
            return 0;
        }

        // fallback, if more than one records are active
        // use latest of all entries for $latestActivity
        $timesheet = null;
        $activeEntries = array_reverse($activeEntries);
        foreach ($activeEntries as $currentEntry) {
            $timesheet = $currentEntry;
            $this->objectManager->getRepository(Timesheet::class)->stopRecording($currentEntry);
        }

        // when called from inside other method, this doesn't need to be called
        if ($manageLatestActivity) {
            $this->manageLatestActivity($user, $timesheet, $action);
        }

        $this->objectManager->flush();

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

        $this->objectManager->persist($entry);

        $this->manageLatestActivity($user, $entry);
        $this->objectManager->flush();

        return $entry;
    }
}
