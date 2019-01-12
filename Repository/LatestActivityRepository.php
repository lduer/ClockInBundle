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
     */
    public function getLatestActivity(User $user)
    {
        $qb = $this->createQueryBuilder('la');

        $qb
            ->where('la.user = :user')
            ->setParameter(':user', $user);

        $result = $qb->getQuery()->getSingleResult();

        if (empty($result)) {
            return null;
        }

        return $result;
    }
}
