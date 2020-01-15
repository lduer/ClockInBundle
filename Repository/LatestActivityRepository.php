<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;

class LatestActivityRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
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

    /**
     * @param LatestActivity $timesheet
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(LatestActivity $timesheet)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($timesheet);
        $entityManager->flush();
    }

    /**
     * @param LatestActivity $latestActivity
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateLatestActivity(LatestActivity $latestActivity)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($latestActivity);
        $entityManager->flush();
    }

    /**
     * @param $latestActivity
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeLatestActivity($latestActivity)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($latestActivity);
        $entityManager->flush();
    }
}
