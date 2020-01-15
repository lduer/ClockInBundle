<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Timesheet;
use App\Model\DashboardSection;
use App\Model\Widget;
use App\Repository\TimesheetRepository;
use App\Repository\WidgetRepository;
use http\Exception\InvalidArgumentException;
use KimaiPlugin\ClockInBundle\ClockIn\Service;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;
use KimaiPlugin\ClockInBundle\Form\ClockInButtonForm;
use KimaiPlugin\ClockInBundle\Form\ClockInForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Dashboard controller for the admin area.
 *
 * @Route(path="/clock-in", name="clock_in_")
 * @Security("is_granted('ROLE_USER')")
 */
class ClockInController extends AbstractController
{
    public static $error_msg = [
        LatestActivity::ACTIVITY_START => 'timesheet.is-running',
        LatestActivity::ACTIVITY_RESUME => 'timesheet.is-running',
        LatestActivity::ACTIVITY_STOP => 'timesheet.all-stopped',
        LatestActivity::ACTIVITY_PAUSE => 'timesheet.all-stopped',
    ];

    /**
     * @var WidgetRepository
     */
    protected $repository;

    /**
     * @var ValidatorInterface
     */
    protected $validator;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Service
     */
    protected $clockInService;

    /**
     * @param TimesheetRepository $repository
     * @param ValidatorInterface $validator
     * @param TranslatorInterface $translator
     * @param Service $service
     */
    public function __construct(TimesheetRepository $repository, ValidatorInterface $validator, TranslatorInterface $translator, Service $service)
    {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->clockInService = $service;
    }

    /**
     * @Route(path="/", defaults={}, name="index", methods={"GET", "POST"})
     * @Security("is_granted('create_own_timesheet')")
     */
    public function indexAction(Request $request)
    {
        $latestActivity = $this->clockInService->findLatestActivity($this->getUser());

        $form = $this->getClockInButtonForm($latestActivity);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach (LatestActivity::$action_list as $action) {
                if ($form->get($action)->isClicked()) {
                    return $this->handleAction($action, $request);
                }
            }

            if ($form->get('activity')->isClicked()) {
                // handle activities
                return $this->redirectToRoute('clock_in_handle_activities');
            }
        }

        return $this->render('@ClockIn/clock-in/index.html.twig', [
            'form' => $form->createView(),
            'latest_activity' => $latestActivity,
            'clock_in_activity_id' => $this->clockInService->getClockInActivityId()
        ]);
    }

    /**
     * @Route(path="/handle/activities", defaults={}, name="handle_activities", methods={"GET", "POST"})
     * @Security("is_granted('create_own_timesheet')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function handleActivitiesAction(Request $request)
    {
        $timesheet = new Timesheet();

        $searchForm = $this->getSearchFieldForm();
        $clockInForm = $this->getClockInForm($timesheet);

        $clockInForm->handleRequest($request);
        if ($clockInForm->isSubmitted()) {
            $timesheet->setBegin(new \DateTime());
            $timesheet->setUser($this->getUser());

            try {
                $errors = $this->validator->validate($timesheet);

                if (count($errors) > 0) {
                    throw new InvalidArgumentException($errors[0]->getPropertyPath() . ' = ' . $errors[0]->getMessage());
                }

                $this->clockInService->startTimesheet($timesheet);

                $this->flashSuccess('timesheet.start.success');
            } catch (\Exception $ex) {
                $this->flashError('timesheet.start.error', ['%reason%' => $ex->getMessage()]);
            }

            $route = 'clock_in_index';

            return $this->redirectToRoute($route);
        }

        return $this->render('@ClockIn/clock-in/handle_activities.html.twig', [
            'clock_in_form' => $clockInForm->createView(),
            'search_form' => $searchForm->createView()
        ]);
    }

    /**
     * @Route(path="/handle/{action}", defaults={}, name="handle", methods={"GET"})
     * @Security("is_granted('create_own_timesheet')")
     *
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function handleAction($action)
    {
        $route = 'clock_in_index';

        if (empty($action)) {
            return $this->redirectToRoute($route);
        } elseif (!isset(LatestActivity::$action_list[$action])) {
            throw $this->createNotFoundException(sprintf('Path not found! Parameter for $action is "%s", but must be one of: %s', $action, implode(', ', LatestActivity::$action_list)));
        }

        try {
            $response = $this->clockInService->{$action}($this->getUser());

            if (is_int($response)) {
                $reason = ClockInController::$error_msg[$action];
                $reason = $this->translator->trans($reason, [], 'exceptions');
                $this->flashError('timesheet.' . $action . '.error', ['%reason%' => $reason]);
            } else {
                $this->flashSuccess('timesheet.' . $action . '.success');
            }
        } catch (\Exception $ex) {
            $this->flashError('timesheet.' . $action . '.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute($route);
    }

    /**
     * The route to stop all running entries from user and reset the latestActivity State
     *
     * @Route(path="/reset_state", name="reset_state", methods={"GET"})
     * @Security("is_granted('create_own_timesheet')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function resetStateAction()
    {
        $latestActivity = $this->clockInService->findLatestActivity($this->getUser());

        if ($latestActivity !== null) {
            $this->clockInService->removeLatestActivity($latestActivity);
        }

        $em = $this->getDoctrine()->getManager();

        $em->getRepository(Timesheet::class)->stopActiveEntries($this->getUser(), 1);

        $this->flashSuccess('recent-activity.reset.success');

        return $this->redirectToRoute('clock_in_index');
    }

    /**
     * @param Timesheet $timesheet
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getClockInForm(Timesheet $timesheet)
    {
        return $this->createForm(ClockInForm::class, $timesheet, [
            'action' => $this->generateUrl('clock_in_handle_activities', [
            ]),
            'method' => 'POST',
        ]);
    }

    /**
     * @param null|LatestActivity $latestActivity
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getClockInButtonForm(LatestActivity $latestActivity = null)
    {
        return $this->createForm(ClockInButtonForm::class, $latestActivity, [
            'action' => $this->generateUrl('clock_in_index', [
            ]),
            'method' => 'POST',
        ]);
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getSearchFieldForm()
    {
        return $this->createFormBuilder()
            ->add('search', TextType::class, [
                'attr' => [
                    'placeholder' => 'search-projects-customers',
                    'class' => 'search_project_field'
                ]
            ])->getForm();
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getDurationWeekWidget()
    {
        $widget = [
            'title' => 'stats.durationWeek',
            'query' => 'duration',
            'user' => true,
            'begin' => new \DateTime('monday this week 00:00:00'),
            'end' => new \DateTime('sunday this week 00:00:00'),
            'icon' => 'duration',
            'color' => 'blue'
        ];

        $data = $this->repository->getStatistic($widget['query'], $widget['begin'], $widget['end'], $this->getUser());

        $row = new DashboardSection(null);

        $model = new Widget($widget['title'], $data);
        $model
            ->setColor($widget['color'])
            ->setIcon($widget['icon'])
            ->setType(Widget::TYPE_COUNTER);

        $row->addWidget($model);

        if ($widget['query'] == TimesheetRepository::STATS_QUERY_DURATION) {
            $model->setDataType(Widget::DATA_TYPE_DURATION);
        } elseif ($widget['query'] == TimesheetRepository::STATS_QUERY_RATE) {
            $model->setDataType(Widget::DATA_TYPE_MONEY);
        }

        return [$row];
    }
}
