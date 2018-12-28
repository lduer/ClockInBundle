<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Controller;

use App\Controller\TimesheetController as TimesheetControllerBase;
use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
     * @Route(path="/export", name="timesheet_export", methods={"GET"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request)
    {
        $query = new TimesheetQuery();

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimesheetQuery $query */
            $query = $form->getData();
            if (null !== $query->getBegin()) {
                $query->getBegin()->setTime(0, 0, 0);
            }
            if (null !== $query->getEnd()) {
                $query->getEnd()->setTime(23, 59, 59);
            }
        }

        $query->setUser($this->getUser());

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('timesheet/export.html.twig', [
            'entries' => $entries,
            'query' => $query,
        ]);
    }

    /**
     * The "main button and fly-out" for displaying (and stopping) active entries.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function activeEntriesAction()
    {
        $user = $this->getUser();
        $activeEntries = $this->getRepository()->getActiveEntries($user);

        return $this->render(
            'navbar/active-entries.html.twig',
            ['entries' => $activeEntries]
        );
    }

    /**
     * The route to stop a running entry.
     *
     * @Route(path="/{id}/stop", name="timesheet_stop", methods={"GET"})
     * @Security("is_granted('stop', entry)")
     *
     * @param Timesheet $entry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry)
    {
        return $this->stop($entry, 'timesheet');
    }

    /**
     * The route to re-start a timesheet entry.
     *
     * @Route(path="/start/{id}", name="timesheet_start", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     * @Security("is_granted('start', timesheet)")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function startAction(ValidatorInterface $validator, Timesheet $timesheet)
    {
        return parent::startAction($validator, $timesheet);


        return $this->redirectToRoute('timesheet');
    }

    /**
     * The route to create a new entry by form.
     *
     * @Route(path="/create", name="timesheet_create", methods={"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        return $this->create($request, 'timesheet', 'timesheet/edit.html.twig');
    }
}
