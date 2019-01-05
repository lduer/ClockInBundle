<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Activity;
use App\Model\DashboardSection;
use App\Model\Widget;
use App\Repository\TimesheetRepository;
use App\Repository\WidgetRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Dashboard controller for the admin area.
 *
 * @Route(path="/")
 * @Security("is_granted('ROLE_USER')")
 */
class ClockInController extends AbstractController
{
    /**
     * @var WidgetRepository
     */
    protected $repository;

    /**
     * @param TimesheetRepository $repository
     */
    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @Route(path="/", defaults={}, name="index", methods={"GET"})
     */
    public function indexAction()
    {
        return $this->render('@KimaiClockIn/clock-in/index.html.twig', [
            'widget_rows' => $this->getDurationWeekWidget(),
            'recent_activities' => $this->getRecentActivities(1)
        ]);
    }

    /**
     * @param int|null $number
     * @return mixed
     * @throws \Exception
     */
    protected function getRecentActivities(int $number = null)
    {
        $user = $this->getUser();
        $repository = $this->getDoctrine()->getRepository(Activity::class);

        $entries = $repository->getRecentActivities($user, new \DateTime('-30 days'));

        if (null !== $number) {
            $entries = array_slice($entries, 0, $number);
        }
        return $entries;
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
            ->setType(Widget::TYPE_COUNTER)
        ;
        $row->addWidget($model);

        if ($widget['query'] == TimesheetRepository::STATS_QUERY_DURATION) {
            $model->setDataType(Widget::DATA_TYPE_DURATION);
        } elseif ($widget['query'] == TimesheetRepository::STATS_QUERY_RATE) {
            $model->setDataType(Widget::DATA_TYPE_MONEY);
        }

        return [$row];
    }
}
