<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\Entity;

use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use http\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LatestActivity
 * @ORM\Table(
 *     name="kimai2_latest_activity",
 *     indexes={
 *          @ORM\Index(columns={"user"})
 *     }
 * )
 * @ORM\Table(name="kimai2_latest_activity")
 * @ORM\Entity(repositoryClass="KimaiPlugin\ClockInBundle\Repository\LatestActivityRepository")
 */
class LatestActivity
{
    public const ACTIVITY_START = 'start';
    public const ACTIVITY_PAUSE = 'pause';
    public const ACTIVITY_RESUME = 'resume';
    public const ACTIVITY_STOP = 'stop';

    public static $action_list = [
        self::ACTIVITY_START => self::ACTIVITY_START,
        self::ACTIVITY_PAUSE => self::ACTIVITY_PAUSE,
        self::ACTIVITY_RESUME => self::ACTIVITY_RESUME,
        self::ACTIVITY_STOP => self::ACTIVITY_STOP,
    ];

    public static $icons = [
        self::ACTIVITY_START => ['class' => 'far fa-play-circle', 'color' => 'green'],
        self::ACTIVITY_PAUSE => ['class' => 'fas fa-pause text-blue', 'color' => 'blue'],
        self::ACTIVITY_RESUME => ['class' => 'fas fa-redo-alt', 'color' => 'blue'],
        self::ACTIVITY_STOP => ['class' => 'far fa-stop-circle', 'color' => 'red']
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Timesheet
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Timesheet")
     * @ORM\JoinColumn(name="timesheet_id", onDelete="SET NULL", nullable=true)
     */
    private $timesheet;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetime", nullable=false)
     * @Assert\NotNull()
     */
    private $time;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=10, nullable=true)
     */
    private $action;

    /**
     * LatestActivity constructor.
     *
     * @param Timesheet $entity
     * @param null|string $action
     * @param null|User $user
     */
    public function __construct(Timesheet $entity, string $action = null, User $user = null)
    {
        $this->setTimesheet($entity);
        $this->setAction($action);
        if (null !== $user) {
            $this->setUser($user);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Timesheet
     */
    public function getTimesheet()
    {
        return $this->timesheet;
    }

    /**
     * @param null|Timesheet $timesheet
     * @return LatestActivity
     */
    public function setTimesheet(Timesheet $timesheet = null)
    {
        $this->timesheet = $timesheet;

        if (null === $timesheet) {
            return $this;
        }

        if (null === $this->timesheet->getEnd()) {
            $this->time = $this->timesheet->getBegin();
        } else {
            $this->time = $this->timesheet->getEnd();
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param \DateTime $time
     * @return LatestActivity
     */
    public function setTime(\DateTime $time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return LatestActivity
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction($action = null)
    {
        if ($action === null || isset(self::$action_list[$action])) {
            $this->action = $action;

            return $this;
        }

        throw new InvalidArgumentException(sprintf('Variable $action must be one of the values: %s.', implode(', ', self::$action_list)));
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return \App\Entity\Activity|null
     */
    public function getActivity()
    {
        if (null === $this->getTimesheet() || null === ($activity = $this->getTimesheet()->getActivity())) {
            return null;
        }

        return $activity;
    }

    /**
     * @return int
     */
    public function getActivityId()
    {
        if (null === $this->getActivity()) {
            return 0;
        }

        return $this->getActivity()->getId();
    }

    /**
     * @return string
     */
    public function getActivityName()
    {
        if (null === $this->getActivity()) {
            return '';
        }

        $string = $this->getActivity()->getName();

        if (null !== $this->timesheet->getDescription()) {
            $string .= ': ' . $this->timesheet->getDescription();
        }

        return $string;
    }

    /**
     * @return string
     */
    public function getProjectName()
    {
        if (null === $this->timesheet) {
            return '';
        }

        return $this->timesheet->getProject()->getName() . ' (' . $this->timesheet->getProject()->getCustomer()->getName() . ')';
    }

    /**
     * @return array
     */
    public function getIcon()
    {
        if (null == $this->getAction()) {
            return [];
        }

        return self::$icons[$this->getAction()];
    }

    /**
     * @return \App\Entity\Project
     */
    public function getProject()
    {
        if (null === $this->timesheet) {
            return null;
        }

        return $this->timesheet->getProject();
    }
}
