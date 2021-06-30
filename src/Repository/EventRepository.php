<?php

/*
 * This file is part of By Night.
 * (c) 2013-2021 Guillaume Sainthillier <guillaume.sainthillier@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\App\Location;
use App\Contracts\ExternalIdentifiableRepositoryInterface;
use App\Entity\Event;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository implements ExternalIdentifiableRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * {@inheritDoc}
     *
     * @return Event[]
     */
    public function findAllByExternalIds(array $externalIds): array
    {
        return $this
            ->createQueryBuilder('e')
            ->where('e.externalId IN (:externalIds)')
            ->setParameter('externalIds', $externalIds)
            ->getQuery()
            ->execute();
    }

    /**
     * User in types.event.persistence.provider.query_builder_method (fos_elastica.yaml)
     */
    public function createIsActiveQueryBuilder(): QueryBuilder
    {
        $from = new DateTime();
        $from->modify(Event::INDEX_FROM);

        $qb = $this->createElasticaQueryBuilder('a');

        return $qb
            ->where('a.dateFin >= :from')
            ->setParameters([
                'from' => $from->format('Y-m-d'),
            ]);
    }

    public function createElasticaQueryBuilder(string $alias, $indexBy = null): QueryBuilder
    {
        return $this
            ->createQueryBuilder($alias, $indexBy)
            ->addSelect('c3')
            ->join('p.country', 'c3');
    }

    public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        $qb = parent::createQueryBuilder($alias, $indexBy);

        return $qb->select($alias, 'p')
            ->addSelect('c')
            ->addSelect('c2')
            ->join($alias . '.place', 'p')
            ->leftJoin('p.city', 'c')
            ->leftJoin('c.parent', 'c2');
    }

    public function createSimpleQueryBuilder(string $alias, $indexBy = null): QueryBuilder
    {
        return parent::createQueryBuilder($alias, $indexBy);
    }

    public function findSiteMap(int $page, int $resultsPerPage): iterable
    {
        return $this
            ->createQueryBuilder('a')
            ->addSelect('c3')
            ->join('p.country', 'c3')
            ->select('a.slug, a.id, a.updatedAt, a.dateFin, c.slug AS city_slug, c3.slug AS country_slug')
            ->setFirstResult($page * $resultsPerPage)
            ->setMaxResults($resultsPerPage)
            ->getQuery()
            ->toIterable();
    }

    public function getSiteMapCount(): int
    {
        return (int) $this
            ->createQueryBuilder('a')
            ->addSelect('c3')
            ->join('p.country', 'c3')
            ->select('COUNT(a) as nb')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function updateNonIndexables(): void
    {
        $from = new DateTime();
        $from->modify(Event::INDEX_FROM);

        $this
            ->_em
            ->createQuery('UPDATE App:Event a
            SET a.archive = true
            WHERE a.dateFin < :from
            AND a.archive = false')
            ->setParameters([
                'from' => $from->format('Y-m-d'),
            ])
            ->execute();
    }

    public function findNonIndexablesBuilder(): QueryBuilder
    {
        $from = new DateTime();

        $from->modify(Event::INDEX_FROM);

        return $this
            ->createElasticaQueryBuilder('a')
            ->where('a.archive = false')
            ->andWhere('a.dateFin < :from')
            ->setParameters([
                'from' => $from->format('Y-m-d'),
            ])
            ->addOrderBy('a.id');
    }

    public function findAllByUser(UserInterface $user): Query
    {
        return $this
            ->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameters(['user' => $user])
            ->orderBy('a.id', 'DESC')
            ->getQuery();
    }

    public function getCountryEvents(): array
    {
        $from = new DateTime();

        return $this->_em
            ->createQueryBuilder()
            ->select('c.displayName, c.atDisplayName, c.slug, COUNT(a.id) AS events')
            ->from('App:Event', 'a')
            ->join('a.place', 'p')
            ->join('p.country', 'c')
            ->where('a.dateFin >= :from')
            ->setParameter('from', $from->format('Y-m-d'))
            ->orderBy('events', 'DESC')
            ->groupBy('c.id')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @return int[]
     *
     * @psalm-return array<int>
     */
    public function getStatsUser(User $user, string $groupByFunction): array
    {
        $datas = $this->_em
            ->createQueryBuilder()
            ->select(sprintf('%s(a.dateFin) as group', $groupByFunction))
            ->addSelect('count(a.id) as events')
            ->from($this->_entityName, 'a')
            ->join('a.userEvents', 'c')
            ->join('c.user', 'u')
            ->where('u.id = :user')
            ->setParameters([':user' => $user->getId()])
            ->groupBy('group')
            ->getQuery()
            ->getScalarResult();

        $ordered = [];
        foreach ($datas as $data) {
            $ordered[$data['group']] = (int) $data['events'];
        }

        return $ordered;
    }

    public function findAllPlaces(User $user, $limit = 5): array
    {
        return $this->_em
            ->createQueryBuilder()
            ->select('COUNT(u) as nbEtablissements, p.nom')
            ->from('App:UserEvent', 'c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.event', 'a')
            ->join('a.place', 'p')
            ->where('c.user = :user')
            ->groupBy('p.nom')
            ->orderBy('nbEtablissements', 'DESC')
            ->setParameters([':user' => $user->getId()])
            ->setFirstResult(0)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAllNextEvents(User $user, bool $isNext = true, $page = 1, $limit = 3): array
    {
        return $this
            ->createQueryBuilder('a')
            ->join('a.userEvents', 'cal')
            ->where('cal.user = :user')
            ->andWhere('a.dateFin ' . ($isNext ? '>=' : '<') . ' :date_debut')
            ->orderBy('a.dateFin', $isNext ? 'ASC' : 'DESC')
            ->setParameters([':user' => $user->getId(), 'date_debut' => date('Y-m-d')])
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function getCountFavorites(User $user): int
    {
        return (int) $this
            ->_em
            ->createQueryBuilder()
            ->select('COUNT(u)')
            ->from('App:UserEvent', 'c')
            ->leftJoin('c.user', 'u')
            ->where('c.user = :user')
            ->setParameters([':user' => $user->getId()])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getParticipationTrendsCount(Event $event): int
    {
        return $this->getTrendsCount($event);
    }

    public function getInteretTrendsCount(Event $event): int
    {
        return $this->getTrendsCount($event, false);
    }

    protected function getTrendsCount(Event $event, bool $isParticipation = true): int
    {
        return (int) $this->_em
            ->createQueryBuilder()
            ->select('COUNT(u)')
            ->from('App:UserEvent', 'c')
            ->leftJoin('c.user', 'u')
            ->where('c.event = :event')
            ->andWhere(($isParticipation ? 'c.participe' : 'c.interet') . ' = :vrai')
            ->setParameters([':event' => $event->getId(), 'vrai' => true])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllTrends(Event $event, $page = 1, $limit = 7)
    {
        return $this
            ->_em
            ->createQueryBuilder()
            ->select('u')
            ->addSelect('c')
            ->addSelect('COUNT(u.id) AS nb_events')
            ->from('App:User', 'u')
            ->join('u.userEvents', 'c')
            ->leftJoin('u.userEvents', 'c2')
            ->where('c.event = :event')
            ->orderBy('nb_events', 'DESC')
            ->groupBy('u.id')
            ->setParameters([':event' => $event->getId()])
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findAllSimilars(Event $event, ?int $page = 1, int $limit = 7)
    {
        return $this
            ->getFindAllSimilarsBuilder($event)
            ->orderBy('a.nom', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    private function getFindAllSimilarsBuilder(Event $event): QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('a')
            ->where('a.dateDebut = :from')
            ->andWhere('a.id != :id')
            ->setParameters([
                ':from' => $event->getDateDebut()->format('Y-m-d'),
                ':id' => $event->getId(),
            ]);

        if (null !== $event->getPlace()->getCity()) {
            $qb
                ->andWhere('p.city = :city')
                ->setParameter('city', $event->getPlace()->getCity()->getId());
        } elseif (null !== $event->getPlace()->getCountry()) {
            $qb
                ->andWhere('p.country = :country')
                ->setParameter('country', $event->getPlace()->getCountry()->getId());
        }

        return $qb;
    }

    public function getAllSimilarsCount(Event $event): int
    {
        return (int) $this
            ->getFindAllSimilarsBuilder($event)
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllNext(Event $event, int $page = 1, int $limit = 7): array
    {
        $from = new DateTime();

        return $this
            ->createQueryBuilder('a')
            ->where('a.dateFin >= :date_fin AND a.id != :id AND a.place = :place')
            ->orderBy('a.dateFin', 'ASC')
            ->setParameters([':date_fin' => $from->format('Y-m-d'), ':id' => $event->getId(), ':place' => $event->getPlace()->getId()])
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function getAllNextCount(Event $event): int
    {
        $from = new DateTime();

        return (int) $this
            ->_em
            ->createQueryBuilder()
            ->select('count(a.id)')
            ->from('App:Event', 'a')
            ->where('a.dateFin >= :date_fin AND a.id != :id AND a.place = :place')
            ->setParameters([':date_fin' => $from->format('Y-m-d'), ':id' => $event->getId(), ':place' => $event->getPlace()->getId()])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTopEventCount(Location $location): int
    {
        return (int) $this
            ->getTopEventBuilder($location)
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getTopEventBuilder(Location $location): QueryBuilder
    {
        $du = new DateTime();
        $au = new DateTime('sunday this week');

        $qb = $this
            ->createQueryBuilder('a')
            ->where('a.dateFin BETWEEN :from AND :to');

        if ($location->isCity()) {
            $qb
                ->andWhere('c.id = :city')
                ->setParameter('city', $location->getCity()->getId());
        } elseif ($location->isCountry()) {
            $qb
                ->andWhere('p.country = :country')
                ->setParameter('country', $location->getCountry()->getId());
        }

        return $qb
            ->setParameter('from', $du->format('Y-m-d'))
            ->setParameter('to', $au->format('Y-m-d'));
    }

    public function findTopEvents(Location $location, int $page = 1, int $limit = 7): array
    {
        return $this
            ->getTopEventBuilder($location)
            ->orderBy('a.dateFin', 'ASC')
            ->addOrderBy('a.participations', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function findUpcomingEvents(Location $location): Query
    {
        $from = new DateTime();

        $qb = $this
            ->createQueryBuilder('a')
            ->where('a.dateFin >= :from')
            ->setParameter('from', $from->format('Y-m-d'))
            ->orderBy('a.dateFin', 'ASC')
            ->addOrderBy('a.participations', 'DESC');

        $this->buildLocationParameters($qb, $location);

        return $qb->getQuery();
    }

    private function buildLocationParameters(QueryBuilder $queryBuilder, Location $location): void
    {
        if ($location->isCountry()) {
            $queryBuilder
                ->andWhere('p.country = :country')
                ->setParameter('country', $location->getCountry()->getId());
        } elseif ($location->isCity()) {
            $queryBuilder
                ->andWhere('p.city = :city')
                ->setParameter('city', $location->getCity()->getId());
        }
    }

    /**
     * @return string[]
     */
    public function getEventTypes(Location $location): array
    {
        $from = new DateTime();
        $from->modify(Event::INDEX_FROM);

        $qb = $this->_em
            ->createQueryBuilder()
            ->select('a.categorieManifestation')
            ->from('App:Event', 'a')
            ->join('a.place', 'p')
            ->where("a.categorieManifestation != ''")
            ->andWhere('a.dateFin >= :from');

        if ($location->isCity()) {
            $qb->andWhere('p.city = :city')
                ->setParameter('city', $location->getCity()->getId());
        } elseif ($location->isCountry()) {
            $qb->andWhere('p.city IS NULL')
                ->andWhere('p.country = :country')
                ->setParameter('country', $location->getCountry()->getId());
        }

        $results = $qb
            ->setParameter('from', $from->format('Y-m-d'))
            ->groupBy('a.categorieManifestation')
            ->orderBy('a.categorieManifestation', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map('current', $results);
    }
}
