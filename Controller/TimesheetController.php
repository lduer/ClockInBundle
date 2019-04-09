<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Controller;

use App\Configuration\TimesheetConfiguration;
use App\Controller\TimesheetController as TimesheetControllerBase;
use App\Entity\Timesheet;
use App\Timesheet\UserDateTimeFactory;
use LDuer\KimaiClockInBundle\ClockIn\Service;
use LDuer\KimaiClockInBundle\Entity\LatestActivity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @param UserDateTimeFactory $dateTime
     * @param TimesheetConfiguration $configuration
     */
    public function __construct(Service $clockInService, UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration)
    {
        $this->clockInService = $clockInService;
        parent::__construct($dateTime, $configuration);
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
        $response = $this->clockInService->pause($this->getUser());
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

    /**
     * Prevent User from creating timesheets in create-form without notice of latestActivity
     *
     * @Route(path="/create", name="timesheet_create", methods={"GET", "POST"})
     * @Security("is_granted('create_own_timesheet')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        // todo: is ist required to create a new timesheet? check permissions!
        return parent::createAction($request);
//        if ($this->clockInService->findLatestActivity()->getAction() === LatestActivity::ACTIVITY_STOP) {
//            return $this->redirectToRoute('clock_in_handle', ['action' => LatestActivity::ACTIVITY_START]);
//        } elseif ($this->clockInService->findLatestActivity()->getAction() === LatestActivity::ACTIVITY_PAUSE) {
//            return $this->redirectToRoute('clock_in_handle', ['action' => LatestActivity::ACTIVITY_RESUME]);
//        }
//
//        return $this->redirectToRoute('clock_in_index');
    }

    /**
     * Catch "edit" action and check if current timesheet is modified, then modify latestActivity entry.
     *
     * @Route(path="/{id}/edit", name="timesheet_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Timesheet $entry, Request $request)
    {
        $route = 'timesheet';
        if (null !== $request->get('page')) {
            $route = 'timesheet_paginated';
        } elseif ('calendar' === $request->get('origin')) {
            $route = 'calendar';
        }

        $previousEndDate = null;
        $latestActivityTimesheet = $this->clockInService->findLatestActivityTimesheet();

        if (null !== $latestActivityTimesheet && $latestActivityTimesheet->getId() === $entry->getId()) {
            if ($latestActivityTimesheet->getEnd() !== null) {
                $previousEndDate = clone $latestActivityTimesheet->getEnd();
            }
        }

        $editForm = $this->getEditForm($entry, $request->get('page'), $request->get('origin', 'timesheet'));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if ($editForm->has('duration')) {
                /** @var Timesheet $record */
                $record = $editForm->getData();
                $duration = $editForm->get('duration')->getData();
                $end = null;
                if ($duration > 0) {
                    $end = clone $record->getBegin();
                    $end->modify('+ ' . $duration . 'seconds');
                }
                $record->setEnd($end);
            }

            // check if enddate/endtime is modified
            if ($previousEndDate !== $entry->getEnd()) {
                if (null === $entry->getEnd() && null !== $previousEndDate) {
                    // timesheet was resumed
                    $this->clockInService->findLatestActivity()->setAction(LatestActivity::ACTIVITY_RESUME);
                } elseif (null === $previousEndDate && null !== $entry->getEnd()) {
                    // timesheet was stopped;
                    $this->clockInService->findLatestActivity()->setAction(LatestActivity::ACTIVITY_PAUSE);
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entry);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute($route, ['page' => $request->get('page')]);
        }

        if (null !== $latestActivityTimesheet && $latestActivityTimesheet->getId() === $entry->getId()) {
            $this->flashWarning('timesheet.warning.modify-active');
        }

        return $this->render('timesheet/edit.html.twig', [
            'entry' => $entry,
            'form' => $editForm->createView(),
        ]);
    }
}
