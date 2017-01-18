<?php

namespace TBN\AgendaBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use TBN\AgendaBundle\Entity\Agenda;
use TBN\MainBundle\Controller\TBNController as Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * Description of MenuDroitController
 *
 * @author guillaume
 */
class MenuDroitController extends Controller
{
    const FB_MEMBERS_LIMIT = 50;
    const TWEET_LIMIT = 25;
    const WIDGET_ITEM_LIMIT = 7;

    public function programmeTVAction()
    {
        $parser = $this->get("tbn.programmetv");
        $programmes = $parser->getProgrammesTV();

        $response = $this->render("TBNAgendaBundle:Hinclude:programme_tv.html.twig", [
            "programmes" => $programmes
        ]);

        return $response
            ->setExpires(new \DateTime('tomorrow'))
            ->setSharedMaxAge($this->getSecondsUntilTomorrow())
            ->setPublic()
        ;
    }

    public function twitterAction($max_id = null) {
        $results = $this->get('tbn.social.twitter')->getTimeline($max_id, self::TWEET_LIMIT);

        $nextLink = null;
        if(isset($results['search_metadata']['next_results'])) {
            parse_str($results['search_metadata']['next_results'], $infos);

            if(isset($infos['?max_id'])) {
                $nextLink = $this->generateUrl('tbn_agenda_tweeter_feed', [
                    'max_id' => $infos['?max_id']
                ]);
            }
        }

        if(! isset($results['statuses'])) {
            $results['statuses'] = [];
        }

        $response =  $this->render('TBNAgendaBundle:Hinclude:tweets.html.twig', [
            'tweets' => $results['statuses'],
            'hasNextLink' => $nextLink
        ]);

        if(! $max_id || count($results['statuses']) !== self::TWEET_LIMIT) {
            list($expire, $ttl) = $this->getSecondsUntil(1);
        }else {
            $expire = new \DateTime;
            $expire->modify("+1 year");
            $ttl = 31536000;
        }

        $response->headers->add([
            'X-No-Browser-Cache' => '1'
        ]);

        return $response
            ->setSharedMaxAge($ttl)
            ->setExpires($expire)
        ;
    }

    public function soireesSimilairesAction(Agenda $soiree, $page = 1)
    {
        if ($page <= 0) {
            $page = 1;
        }
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository("TBNAgendaBundle:Agenda");

        $count = $repo->findAllSimilairesCount($soiree);
        $current = $page * self::WIDGET_ITEM_LIMIT;

        if($current < $count) {
            $hasNextLink = $this->generateUrl('tbn_agenda_soirees_similaires', [
                'slug' => $soiree->getSlug(),
                'page' => $page + 1
            ]);
        }else {
            $hasNextLink = null;
        }

        $response = $this->render("TBNAgendaBundle:Hinclude:evenements.html.twig", [
            "soirees" => $repo->findAllSimilaires($soiree, $page, self::WIDGET_ITEM_LIMIT),
            "current" => $current,
            "count" => $count,
            "hasNextLink" => $hasNextLink
        ]);

        $response->headers->add([
            'X-No-Browser-Cache' => '1'
        ]);

        return $response
            ->setExpires(new \DateTime('tomorrow'))
            ->setSharedMaxAge($this->getSecondsUntilTomorrow())
            ->setPublic()
        ;
    }

    public function topSoireesAction($page = 1)
    {
        if ($page <= 1) {
            $page = 1;
        }

        $siteManager = $this->container->get("site_manager");
        $site = $siteManager->getCurrentSite();

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('TBNAgendaBundle:Agenda');

        $current = $page * self::WIDGET_ITEM_LIMIT;
        $count = $repo->findTopSoireeCount($site);

        if($current < $count) {
            $hasNextLink = $this->generateUrl('tbn_agenda_top_soirees', [
                'page' => $page + 1
            ]);
        }else {
            $hasNextLink = null;
        }

        $response = $this->render("TBNAgendaBundle:Hinclude:evenements.html.twig", [
            "soirees" => $repo->findTopSoiree($site, $page, self::WIDGET_ITEM_LIMIT),
            "hasNextLink" => $hasNextLink,
            "current" => $current,
            "count" => $count
        ]);

        $response->headers->add([
            'X-No-Browser-Cache' => '1'
        ]);

        return $response
            ->setExpires(new \DateTime('tomorrow'))
            ->setSharedMaxAge($this->getSecondsUntilTomorrow())
            ->setPublic()
        ;
    }

    /**
     * TODO: Delete this action
     */
    public function tendancesAction(Agenda $soiree)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository("TBNAgendaBundle:Agenda");
        $nbItems = 30;
        $membres = $this->getFBMembres($soiree, 1, $nbItems);

        return $this->render("TBNAgendaBundle:Hinclude:tendances.html.twig", [
            "tendancesParticipations" => $repo->findAllTendancesParticipations($soiree),
            "tendancesInterets" => $repo->findAllTendancesInterets($soiree),
            "count_participer" => $soiree->getParticipations() + $soiree->getFbParticipations(),
            "count_interets" => $soiree->getInterets() + $soiree->getFbInterets(),
            "membres" => $membres,
            "maxItems" => $nbItems
        ]);
    }

    public function fbMembresAction(Agenda $soiree, $page)
    {
        if(! $soiree->getFacebookEventId()) {
            return $this->redirectToRoute('tbn_agenda_details', ['slug' => $soiree->getSlug()]);
        }

        if ($page <= 1) {
            $page = 1;
        }

        $api = $this->get("tbn.social.facebook_admin");
        $retour = $api->getEventMembres($soiree->getFacebookEventId(), ($page - 1) * self::FB_MEMBERS_LIMIT, self::FB_MEMBERS_LIMIT);

        $membres = array_merge($retour['participations'], $retour['interets']);
        if(count($retour['interets']) == self::FB_MEMBERS_LIMIT || count($retour['participations']) == self::FB_MEMBERS_LIMIT) {
            $hasNextLink = $this->generateUrl('tbn_agenda_soirees_membres', [
                'slug' => $soiree->getSlug(),
                'page' => $page + 1
            ]);
        }else {
            $hasNextLink = null;
        }

        $response = $this->render("TBNAgendaBundle:Hinclude:fb_membres.html.twig", [
            "event" => $soiree,
            "page" => $page,
            "membres" => $membres,
            "hasNextLink" => $hasNextLink
        ]);

        try {
            $now = new \DateTime();
            if ($soiree->getDateFin() < $now) {
                $now->modify("+1 year");
                $response
                    ->setExpires($now)
                    ->setSharedMaxAge(31536000);
            } else {
                if($hasNextLink) {
                    list($expires, $next2hours) = $this->getSecondsUntil(24);
                }else {
                    list($expires, $next2hours) = $this->getSecondsUntil(2);
                }

                $response
                    ->setExpires($expires)
                    ->setSharedMaxAge($next2hours);
            }
        }catch(\Exception $e) {
            $this->get('logger')->critical($e);
        }

        $response->headers->add([
           'X-No-Browser-Cache' => '1'
        ]);

        return $response->setPublic();
    }

    public function topMembresAction($page = 1)
    {
        if ($page <= 1) {
            $page = 1;
        }

        $siteManager = $this->container->get("site_manager");
        $site = $siteManager->getCurrentSite();

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository("TBNUserBundle:User");

        $count = $repo->findMembresCount($site);
        $current = $page * self::WIDGET_ITEM_LIMIT;

        if($current < $count) {
            $hasNextLink = $this->generateUrl('tbn_agenda_top_membres', [
                'page' => $page + 1
            ]);
        }else {
            $hasNextLink = null;
        }

        $response = $this->render("TBNAgendaBundle:Hinclude:membres.html.twig", [
            "membres" => $repo->findTopMembres($site, $page, self::WIDGET_ITEM_LIMIT),
            "hasNextLink" => $hasNextLink,
            "current" => $current,
            "count" => $count
        ]);

        list($future, $seconds) = $this->getSecondsUntil(6);

        $response->headers->add([
            'X-No-Browser-Cache' => '1'
        ]);

        return $response
            ->setExpires($future)
            ->setSharedMaxAge($seconds)
            ->setPublic()
        ;
    }
}
