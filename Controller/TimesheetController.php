<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Controller;

use App\Controller\TimesheetController as TimesheetControllerBase;
use App\Entity\Timesheet;
use LDuer\KimaiClockInBundle\ClockIn\Service;
use LDuer\KimaiClockInBundle\Entity\LatestActivity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller used to manage timesheets.
 *
 * @Route(path="/timesheet")
 * @Security("is_granted('ROLE_USER')")
 */
class TimesheetController extends TimesheetControllerBase
{
    /**
     * @var Service
     */
    protected $clockInService;

    /**
     * TimesheetController constructor.
     *
     * @param Service $clockInService
     * @param bool $durationOnly
     */
    public function __construct(Service $clockInService, bool $durationOnly)
    {
        $this->clockInService = $clockInService;
        parent::__construct($durationOnly);
    }

    /**
     * The route to stop a running entry.
     *  -- action is here to overwrite kimai-default actions
     *
     * @Route(path="/{id}/stop", name="timesheet_stop", methods={"GET"})
     * @Security("is_granted('stop', entry)")
     *
     * @param Timesheet $entry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry)
    {
        // ignore entry; stop all entries
        $response = $this->clockInService->stop($this->getUser());
        $action = LatestActivity::ACTIVITY_STOP;

        if (is_int($response)) {
            $reason = ClockInController::$error_msg[$action];
            $reason = $this->translator->trans($reason, [], 'exceptions');
            $this->flashError('timesheet.' . $action . '.error', ['%reason%' => $reason]);
        } else {
            $this->flashSuccess('timesheet.' . $action . '.success');
        }

        return $this->redirectToRoute('clock_in_index');
    }

    /**
     * The route to re-start a timesheet entry.
     *  -- action is here to overwrite kimai-default actions
     *
     * @Route(path="/start/{id}", name="timesheet_start", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     * @Security("is_granted('start', timesheet)")
     *
     * @param Timesheet $timesheet
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function startAction(ValidatorInterface $validator, Timesheet $timesheet)
    {
        try {
            $this->clockInService->startTimesheet($timesheet, $this->getUser());
            $this->flashSuccess('timesheet.start-activity.success');
        } catch (\Exception $ex) {
            $this->flashError('timesheet.start-activity.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('clock_in_index');
    }
}
