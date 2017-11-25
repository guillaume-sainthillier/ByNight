<?php

namespace AppBundle\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * CityRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CityRepository extends \Doctrine\ORM\EntityRepository
{
    public function findRandomNames($limit = 5)
    {
        $results = $this
            ->createQueryBuilder('c')
            ->select('c.name, c.slug')
            ->orderBy('c.population', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getScalarResult();

        \shuffle($results);

        return \array_slice($results, 0, $limit);
    }

    public function findLocations()
    {
        $results = $this->createQueryBuilder('c')
            ->select('c.latitude, c.longitude')
            ->where('c.latitude IS NOT NULL')
            ->andWhere('c.longitude IS NOT NULL')
            ->orderBy('c.population', 'DESC')
            ->getQuery()
            ->setMaxResults(50)
            ->getScalarResult();

        return $results;
    }

    public function findByName($city, $country)
    {
        $cities   = [];
        $city     = \preg_replace("#(^|\s)st\s#i", '$1saint ', $city);
        $city     = \str_replace('’', "'", $city);
        $cities[] = $city;
        $cities[] = \str_replace(' ', '-', $city);
        $cities[] = \str_replace('-', ' ', $city);
        $cities[] = \str_replace("'", '', $city);
        $cities   = \array_unique($cities);

        return $this
            ->createQueryBuilder('c')
            ->where('c.name IN (:cities)')
            ->andWhere('c.country = :country')
            ->setParameter('cities', $cities)
            ->setParameter('country', $country)
            ->getQuery()
            ->setCacheable(true)
            ->setCacheMode(ClassMetadata::CACHE_USAGE_READ_ONLY)
            ->useResultCache(true)
            ->useQueryCache(true)
            ->getResult();
    }

    public function findTopPopulation($maxResults)
    {
        return $this
            ->createQueryBuilder('c')
            ->orderBy('c.population', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->setCacheable(true)
            ->setCacheMode(ClassMetadata::CACHE_USAGE_READ_ONLY)
            ->useResultCache(true)
            ->useQueryCache(true)
            ->getResult();
    }

    public function findBySlug($slug)
    {
        return $this
            ->createQueryBuilder('c')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->setCacheable(true)
            ->setCacheMode(ClassMetadata::CACHE_USAGE_READ_ONLY)
            ->useResultCache(true)
            ->useQueryCache(true)
            ->getOneOrNullResult();
    }

    public function findAllCities()
    {
        $cities = $this
            ->createQueryBuilder('c')
            ->select('c.name')
            ->where('c.population > 10000')
            ->groupBy('c.name')
            ->getQuery()
            ->getScalarResult();

        return \array_unique(\array_filter(\array_column($cities, 'name')));
    }
}
