<?php

namespace App\App;

use App\Entity\City;
use App\Entity\Country;

class Location
{
    /** @var City|null */
    private $city;

    /** @var Country|null */
    private $country;

    public function getAppName()
    {
        if ($this->city) {
            return sprintf('%s By Night', $this->city->getName());
        }

        return sprintf('By Night %s', $this->country->getDisplayName());
    }

    public function getAtName()
    {
        if ($this->city) {
            return sprintf('à %s', $this->city->getName());
        }

        if ($this->country) {
            return $this->country->getAtDisplayName();
        }

        return '';
    }

    public function getName()
    {
        if ($this->city) {
            return $this->city->getName();
        }

        if ($this->country) {
            return $this->country->getDisplayName();
        }

        return '';
    }

    public function getSlug()
    {
        if ($this->city) {
            return $this->city->getSlug();
        }

        if ($this->country) {
            return $this->country->getSlug();
        }

        return 'unknown';
    }

    public function isCity()
    {
        return null !== $this->city;
    }

    public function isCountry()
    {
        return null !== $this->country;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }
}
