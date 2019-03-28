<?php

namespace App\Repository;

use App\Entity\ZipCity;
use Doctrine\ORM\EntityRepository;

/**
 * ZipCityRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ZipCityRepository extends EntityRepository
{
    /**
     * @param string|null $postalCode
     * @param string|null $city
     * @param string      $country
     *
     * @return ZipCity[]
     */
    private function findByPostalCodeOrCity($postalCode = null, $city = null, $country = null)
    {
        $query = $this
            ->createQueryBuilder('zc')
            ->where('zc.country = :country')
            ->setParameter('country', $country);

        if ($postalCode) {
            $query
                ->andWhere('zc.postalCode = :postalCode')
                ->setParameter('postalCode', $postalCode);
        }

        if ($city) {
            $cities   = [];
            $city     = \preg_replace("#(^|\s)st\s#i", '$1saint ', $city);
            $city     = \str_replace('’', "'", $city);
            $cities[] = $city;
            $cities[] = \str_replace(' ', '-', $city);
            $cities[] = \str_replace('-', ' ', $city);
            $cities[] = \str_replace("'", '', $city);
            $cities   = \array_unique($cities);

            $query
                ->andWhere('zc.name IN(:cities)')
                ->setParameter('cities', $cities);
        }

        return $query
            ->getQuery()
            ->useQueryCache(true)
            ->useResultCache(true)
            ->getResult();
    }

    /**
     * @param string      $postalCode
     * @param string|null $city
     * @param string      $country
     *
     * @return ZipCity|null
     */
    public function findByPostalCodeAndCity($postalCode, $city, $country)
    {
        $cities = $this->findByPostalCodeOrCity($postalCode, $city, $country);
        if (1 === \count($cities)) {
            return $cities[0];
        }

        return null;
    }

    /**
     * @param string|null $city
     * @param string      $country
     *
     * @return ZipCity[]
     */
    public function findByCity($city, $country)
    {
        return $this->findByPostalCodeOrCity(null, $city, $country);
    }

    /**
     * @param string $postalCode
     * @param string $country
     *
     * @return ZipCity[]
     */
    public function findByPostalCode($postalCode, $country)
    {
        return $this->findByPostalCodeOrCity($postalCode, null, $country);
    }
}
