<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * NewsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NewsRepository extends EntityRepository
{
    public function findNextEdition()
    {
        $result = $this
            ->createQueryBuilder('n')
            ->select('MAX (n.numeroEdition) AS nextEdition')
            ->getQuery()
            ->getSingleScalarResult();

        if (!$result) {
            return 1;
        }

        return intval($result) + 1;
    }
}
