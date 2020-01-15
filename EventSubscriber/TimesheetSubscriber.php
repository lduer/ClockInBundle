<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\EventSubscriber;

use App\Event\TimesheetUpdateEvent;
use KimaiPlugin\ClockInBundle\ClockIn\Service;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A listener to make sure all Timesheet entries will update its clock-in status
 */
class TimesheetSubscriber implements EventSubscriberInterface
{
    public const INSERT = 'insert';
    public const UPDATE = 'update';

    /**
     * @var Service
     */
    protected $service;

    /**
     * TimesheetSubscriber constructor.
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TimesheetUpdateEvent::TIMESHEET_CREATE => 'onTimesheetCreate',
            TimesheetUpdateEvent::TIMESHEET_UPDATE => 'onTimesheetUpdate',
            TimesheetUpdateEvent::TIMESHEET_DELETE => 'onTimesheetDelete',
            TimesheetUpdateEvent::TIMESHEET_STOP => 'onTimesheetUpdate',
            TimesheetUpdateEvent::TIMESHEET_RESTART => 'onTimesheetCreate',
        ];
    }

    /**
     * @param TimesheetUpdateEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onTimesheetCreate(TimesheetUpdateEvent $event)
    {
        $timesheet = $event->getEntity();

        if (null === $timesheet) {
            return;
        }

        if ($timesheet->getEnd() !== null) {
            // entry was created as "closed", do not track
            return;
        }

        $user = $timesheet->getUser();
        $latestActivity = $this->service->manageLatestActivity($user, $timesheet);

        $this->service->updateLatestActivity($latestActivity);
    }

    /**
     * @param TimesheetUpdateEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onTimesheetUpdate(TimesheetUpdateEvent $event)
    {
        $timesheet = $event->getEntity();

        if (null === $timesheet || null === $timesheet->getId()) {
            return;
        }

        $latestActivity = $this->service->findLatestActivity($timesheet->getUser());

        if ($latestActivity->getTimesheet() !== $timesheet) {
            return;
        }

        if ($timesheet->getEnd() === null) {
            // timesheet is running
            $latestActivity->setAction(null);
        } elseif ($latestActivity->getTimesheet() === $timesheet) {
            // timesheet is not running.
            $latestActivity->setAction(LatestActivity::ACTIVITY_PAUSE);
            $latestActivity->setTime($timesheet->getEnd());
        }

        $this->service->updateLatestActivity($latestActivity);
    }

    /**
     * @param TimesheetUpdateEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onTimesheetDelete(TimesheetUpdateEvent $event)
    {
        $timesheet = $event->getEntity();

        if (null === $timesheet) {
            return;
        }

        $latestActivity = $this->service->findLatestActivity($timesheet->getUser());

        if ($latestActivity->getTimesheet() !== $timesheet) {
            return;
        }

        $latestActivity->setAction(LatestActivity::ACTIVITY_STOP);
        $latestActivity->setTimesheet(null);

        $this->service->updateLatestActivity($latestActivity);
    }
}
