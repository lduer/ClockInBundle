<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\Repository;

use App\Entity\User;
use App\Repository\AbstractRepository;
use LDuer\KimaiClockInBundle\Entity\LatestActivity;

class LatestActivityRepository extends AbstractRepository
{
    /**
     * @param User $user
     * @return LatestActivity[]|null
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
}
