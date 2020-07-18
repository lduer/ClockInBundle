<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\Repository;

use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;

class LatestActivityRepository extends EntityRepository
{
    public function getLatestActivity(User $user)
    {
        $qb = $this->createQueryBuilder('la');

        $qb
            ->where('la.user = :user')
            ->setMaxResults(1)
            ->orderBy('la.time', 'DESC')
            ->setParameter(':user', $user);

        $result = $qb->getQuery()->getOneOrNullResult();

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    public function save(LatestActivity $timesheet)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($timesheet);
        $entityManager->flush();
    }

    public function updateLatestActivity(LatestActivity $latestActivity)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($latestActivity);
        $entityManager->flush();
    }

    public function removeLatestActivity(LatestActivity $latestActivity)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($latestActivity);
        $entityManager->flush();
    }

    public function manageLatestActivity(User $user, Timesheet $timesheet, $action = null)
    {
        $latestActivity = $this->getLatestActivity($user);

        if (null === $latestActivity) {
            $latestActivity = new LatestActivity($timesheet, $action, $user);

            $latestActivity
                ->setAction($action)
                ->setTimesheet($timesheet);
        }

        $latestActivity
            ->setTimesheet($timesheet)
            ->setAction($action);

        $this->save($latestActivity);

        return $latestActivity;
    }

    /**
     * @param User|null $user
     * @return Timesheet|null
     */
    public function findLatestActivityTimesheet(User $user = null)
    {
        if (null !== $latestActivity = $this->getLatestActivity($user)) {
            return $latestActivity->getTimesheet();
        }

        return null;
    }
}
