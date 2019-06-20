<?php

namespace App\Tests\Handler;

use App\Entity\Agenda;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Place;
use App\Entity\User;
use App\Handler\EchantillonHandler;
use App\Tests\ContainerTestCase;

class EchantillonHandlerTest extends ContainerTestCase
{
    /**
     * @var EchantillonHandler
     */
    private $echantillonHandler;

    protected function setUp()
    {
        parent::setUp();

        $this->echantillonHandler = static::$container->get(EchantillonHandler::class);

        $this->echantillonHandler->clearPlaces();
        $this->echantillonHandler->clearEvents();
    }

    /**
     * @dataProvider userEventEchantillonsProvider
     */
    public function testUserEventEchantillons(Agenda $event)
    {
        $this->echantillonHandler->prefetchPlaceEchantillons([$event]);
        $this->echantillonHandler->prefetchEventEchantillons([$event]);

        $persistedPlaces = $this->echantillonHandler->getPlaceEchantillons($event);
        $persistedEvents = $this->echantillonHandler->getEventEchantillons($event);

        $this->assertCount(0, $persistedEvents);
        $this->assertCount(0, $persistedPlaces);
    }

    public function userEventEchantillonsProvider()
    {
        return [
            [(new Agenda())->setUser(new User())],
            [(new Agenda())->setId(2917)->setUser(new User())],
            [(new Agenda())->setId(2917)->setExternalId('FB-1537604069794319')->setUser(new User())],
        ];
    }

    /**
     * @dataProvider eventEchantillonProvider
     */
    public function testEventEchantillons(Agenda $event)
    {
        $this->echantillonHandler->prefetchPlaceEchantillons([$event]);

        $this->expectException(\RuntimeException::class);
        $this->echantillonHandler->prefetchEventEchantillons([$event]);
    }

    public function eventEchantillonProvider()
    {
        return [
            [(new Agenda())],
            [(new Agenda())->setPlace(new Place())],
            [(new Agenda())->setPlace((new Place())->setId(1))],
        ];
    }

    public function testAddNewEvent()
    {
        $france = (new Country())->setId('FR');
        $saintLys = (new City())->setId(2978661)->setCountry($france);

        $parsedEvent1 = (new Agenda())->setExternalId('XXX')->setPlace((new Place())->setCity($saintLys));
        $events = [$parsedEvent1];

        $this->echantillonHandler->prefetchPlaceEchantillons($events);
        $this->echantillonHandler->prefetchEventEchantillons($events);

        //There must not have any event candidates for this event
        $persistedPlaces = $this->echantillonHandler->getPlaceEchantillons($parsedEvent1);
        $countPersistedPlaces = \count($persistedPlaces);
        $this->makeAddNewEventAsserts($parsedEvent1, 0, $countPersistedPlaces);

        //After adding event, there is one candidate
        $this->echantillonHandler->addNewEvent($parsedEvent1);
        $this->makeAddNewEventAsserts($parsedEvent1, 1, $countPersistedPlaces + 1);

        //After adding the same event, nothing must have changed
        $this->echantillonHandler->addNewEvent($parsedEvent1);
        $this->makeAddNewEventAsserts($parsedEvent1, 1, $countPersistedPlaces + 1);
    }

    private function makeAddNewEventAsserts(Agenda $event, int $expectedCountEvents, int $expectedCountPlaces)
    {
        $persistedEvents = $this->echantillonHandler->getEventEchantillons($event);
        $persistedPlaces = $this->echantillonHandler->getPlaceEchantillons($event);
        $this->assertCount($expectedCountEvents, $persistedEvents);
        $this->assertCount($expectedCountPlaces, $persistedPlaces);

        if (1 === $expectedCountEvents) {
            $this->assertEquals($event, $persistedEvents[0]);
        }
    }

    public function testPlacesEchantillons()
    {
        $france = (new Country())->setId('FR');
        $saintLys = (new City())->setId(2978661)->setCountry($france);

        $eventWithCity = (new Agenda())->setPlace((new Place())->setCity($saintLys));
        $eventWithExternalId = (new Agenda())->setPlace((new Place())->setCity($saintLys)->setExternalId('FB-108032189219838'));
        $eventWithCountry = (new Agenda())->setPlace((new Place())->setCountry($france));

        $events = [$eventWithCity, $eventWithExternalId, $eventWithCountry];
        $this->echantillonHandler->prefetchPlaceEchantillons($events);

        //Check that echantillon places must be in Saint-Lys
        $persistedPlaces = $this->echantillonHandler->getPlaceEchantillons($eventWithCity);
        $this->assertNotCount(0, $persistedPlaces);
        foreach ($persistedPlaces as $persistedPlace) {
            $this->assertNotNull($persistedPlace->getId());
            $this->assertNotNull($persistedPlace->getCity());
            $this->assertEquals($saintLys->getId(), $persistedPlace->getCity()->getId());
        }

        //Check that there is only one echantillon with the same externalID
        $persistedPlaces = $this->echantillonHandler->getPlaceEchantillons($eventWithExternalId);
        $this->assertCount(1, $persistedPlaces);
        foreach ($persistedPlaces as $persistedPlace) {
            $this->assertNotNull($persistedPlace->getId());
            $this->assertNotNull($persistedPlace->getCity());
            $this->assertEquals($saintLys->getId(), $persistedPlace->getCity()->getId());
            $this->assertEquals($eventWithExternalId->getPlace()->getExternalId(), $persistedPlace->getExternalId());
        }

        //Check that echantillon places must be in Saint-Lys
        $persistedPlaces = $this->echantillonHandler->getPlaceEchantillons($eventWithCity);
        $this->assertNotCount(0, $persistedPlaces);
        foreach ($persistedPlaces as $persistedPlace) {
            $this->assertNotNull($persistedPlace->getId());
            $this->assertNotNull($persistedPlace->getCity());
            $this->assertEquals($saintLys->getId(), $persistedPlace->getCity()->getId());
        }

        //Check that echantillon places must be in France
        $persistedPlaces = $this->echantillonHandler->getPlaceEchantillons($eventWithCountry);
        $this->assertNotCount(0, $persistedPlaces);
        foreach ($persistedPlaces as $persistedPlace) {
            $this->assertNotNull($persistedPlace->getCountry());
            $this->assertEquals($france->getId(), $persistedPlace->getCountry()->getId());
            $this->assertNull($persistedPlace->getCity());
        }
    }
}
