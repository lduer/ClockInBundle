<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\EventSubscriber;

use App\Entity\User;
use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetUpdatePostEvent;
use App\Event\TimesheetDeletePreEvent;
use KimaiPlugin\ClockInBundle\ClockIn\ClockInService;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;
use KimaiPlugin\ClockInBundle\Repository\LatestActivityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A listener to make sure all Timesheet entries will update its clock-in status
 */
class TimesheetSubscriber implements EventSubscriberInterface
{
    public const INSERT = 'insert';
    public const UPDATE = 'update';

    /**
     * @var ClockInService
     */
    protected $service;

    /**
     * @var LatestActivityRepository
     */
    protected $latestActivityRepository;

    /**
     * TimesheetSubscriber constructor.
     * @param ClockInService $service
     * @param LatestActivityRepository $latestActivityRepository
     */
    public function __construct(ClockInService $service, LatestActivityRepository $latestActivityRepository)
    {
        $this->service = $service;
        $this->latestActivityRepository = $latestActivityRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TimesheetCreatePostEvent::class => 'onTimesheetCreate',
            TimesheetUpdatePostEvent::class => 'onTimesheetUpdate',
            TimesheetDeletePreEvent::class => 'onTimesheetDelete',
        ];
    }

    /**
     * @param TimesheetCreatePostEvent $event
     */
    public function onTimesheetCreate(TimesheetCreatePostEvent $event)
    {
        $timesheet = $event->getTimesheet();

        if (null === $timesheet) {
            return;
        }

        if ($timesheet->getEnd() !== null) {
            // entry was created as "closed", do not track
            return;
        }

        $user = $timesheet->getUser();

        $latestActivity = $this->latestActivityRepository->manageLatestActivity($user, $timesheet);

        $this->updateLatestActivity($latestActivity);
    }

    /**
     * @param TimesheetUpdatePostEvent $event
     */
    public function onTimesheetUpdate(TimesheetUpdatePostEvent $event)
    {
        $timesheet = $event->getTimesheet();

        if (null === $timesheet || null === $timesheet->getId()) {
            // no (persisted) timesheet found
            return;
        }

        $latestActivity = $this->findLatestActivityTimesheet($timesheet->getUser());

        if ($latestActivity->getTimesheet() !== $timesheet) {
            return;
        }

        if ($timesheet->getEnd() === null) {
            // timesheet is running
            $latestActivity->setAction(null);
            $latestActivity->setTimesheet($timesheet);
            $latestActivity->setTime($timesheet->getBegin());

        } else {
            // timesheet is not running.
            $latestActivity->setAction(LatestActivity::ACTIVITY_PAUSE);
            $latestActivity->setTime($timesheet->getEnd());
        }

        $this->updateLatestActivity($latestActivity);
    }

    /**
     * @param TimesheetDeletePreEvent $event
     */
    public function onTimesheetDelete(TimesheetDeletePreEvent $event)
    {
        $timesheet = $event->getTimesheet();

        if (null === $timesheet) {
            return;
        }

        $latestActivity = $this->findLatestActivityTimesheet($timesheet->getUser());

        if ($latestActivity->getTimesheet() !== $timesheet) {
            return;
        }

        $latestActivity->setAction(LatestActivity::ACTIVITY_STOP);
        $latestActivity->setTimesheet(null);

        $this->updateLatestActivity($latestActivity);
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function findLatestActivityTimesheet(User $user) {
        return $this->latestActivityRepository->findLatestActivityTimesheet($user);
    }

    /**
     * @param $latestActivity
     */
    private function updateLatestActivity($latestActivity) {
        return $this->latestActivityRepository->updateLatestActivity($latestActivity);
    }
}
