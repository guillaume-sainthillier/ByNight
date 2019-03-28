<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CountryRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CountryRepository extends EntityRepository
{
    public function findByName($country)
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('LOWER(c.name) = :country')
            ->setParameter('country', \strtolower($country))
            ->getQuery()
            ->useResultCache(true)
            ->useQueryCache(true)
            ->getOneOrNullResult();
    }
}
