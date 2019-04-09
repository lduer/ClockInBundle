<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Controller;

use App\Configuration\TimesheetConfiguration;
use App\Controller\TimesheetTeamController as TimesheetTeamControllerBase;
use App\Entity\Timesheet;
use App\Timesheet\UserDateTimeFactory;
use LDuer\KimaiClockInBundle\ClockIn\Service;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used for manage timesheet entries in the admin part of the site.
 *
 * @Route(path="/team/timesheet")
 * @Security("is_granted('view_other_timesheet')")
 */
class TimesheetTeamController extends TimesheetTeamControllerBase
{

    /**
     * @var Service
     */
    protected $clockInService;

    /**
     * TimesheetTeamController constructor.
     * @param Service $clockInService
     * @param UserDateTimeFactory $dateTime
     * @param TimesheetConfiguration $configuration
     */
    public function __construct(Service $clockInService, UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration)
    {
        $this->clockInService = $clockInService;
        $this->dateTime = $dateTime;
        $this->configuration = $configuration;
    }

    /**
     * @Route(path="/{id}/stop", name="admin_timesheet_stop", methods={"GET"})
     * @Security("is_granted('stop', entry)")
     *
     * @param Timesheet $entry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry)
    {
        return $this->stop($entry, 'admin_timesheet');
    }

    /**
     * @Route(path="/{id}/edit", name="admin_timesheet_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Timesheet $entry, Request $request)
    {
        $latestActivityTimesheet = $this->clockInService->findLatestActivityTimesheet($entry->getUser());

        if (null !== $latestActivityTimesheet && $latestActivityTimesheet->getId() === $entry->getId()) {
            if (!$request->isMethod('POST')) {
                $this->flashWarning('timesheet.warning.modify-active');
            }
        }

        return $this->edit($entry, $request, 'admin_timesheet_paginated', 'admin/timesheet_edit.html.twig');
    }

    /**
     * @Route(path="/create", name="admin_timesheet_create", methods={"GET", "POST"})
     * @Security("is_granted('create_other_timesheet')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        return $this->create($request, 'admin_timesheet', 'admin/timesheet_edit.html.twig');
    }

    /**
     * @Route(path="/{id}/delete", defaults={"page": 1}, name="admin_timesheet_delete", methods={"GET", "POST"})
     * @Security("is_granted('delete', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Timesheet $entry, Request $request)
    {
        return $this->delete($entry, $request);
    }
}
