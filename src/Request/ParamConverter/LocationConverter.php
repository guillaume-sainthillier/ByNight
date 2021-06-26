<?php

/*
 * This file is part of By Night.
 * (c) 2013-2021 Guillaume Sainthillier <guillaume.sainthillier@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Request\ParamConverter;

use App\App\CityManager;
use App\App\Location;
use App\Entity\City;
use App\Entity\Country;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LocationConverter implements ParamConverterInterface
{
    private CityManager $cityManager;
    private CityRepository $cityRepository;
    private CountryRepository $countryRepository;

    public function __construct(CityManager $cityManager, CityRepository $cityRepository, CountryRepository $countryRepository)
    {
        $this->cityManager = $cityManager;
        $this->cityRepository = $cityRepository;
        $this->countryRepository = $countryRepository;
    }

    public function apply(Request $request, ParamConverter $configuration): void
    {
        $locationSlug = $request->attributes->get('location', '');

        if (null === $locationSlug && !$configuration->isOptional()) {
            throw new InvalidArgumentException('Route attribute is missing');
        } elseif (null === $locationSlug) {
            return;
        }

        if (\is_object($locationSlug)) {
            return;
        }

        if ('unknown' === $locationSlug) {
            $noWhere = new Country();
            $noWhere->setName('Nowhere');
            $noWhere->setSlug($locationSlug);
            $location->setCountry($noWhere);
            $request->attributes->set($configuration->getName(), $location);

            return;
        }

        $location = new Location();
        $entity = null;
        if (0 !== strpos((string) $locationSlug, 'c--')) {
            $entity = $this->cityRepository->findBySlug($locationSlug);
        } else {
            $entity = $this->countryRepository->findOneBy(['slug' => $locationSlug]);
        }

        if ($entity instanceof City) {
            $location->setCity($entity);
            $this->cityManager->setCurrentCity($entity);
            $request->attributes->set('_current_city', $locationSlug);
        } elseif ($entity instanceof Country) {
            $location->setCountry($entity);
        } else {
            throw new NotFoundHttpException(sprintf("La location '%s' est introuvable", $locationSlug));
        }

        $request->attributes->set($configuration->getName(), $location);
    }

    public function supports(ParamConverter $configuration)
    {
        return Location::class === $configuration->getClass();
    }
}
