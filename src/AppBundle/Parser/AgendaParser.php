<?php

namespace AppBundle\Parser;

use AppBundle\Factory\EventFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use AppBundle\Entity\Agenda;
use AppBundle\Entity\Site;
use AppBundle\Entity\SiteInfo;

/*
 * Classe abstraite représentant le parse des données d'un site Internet
 * Plusieurs moyens sont disponibles: Récupérer directement les données suivant
 * une URL donnée, ou bien retourner un tableau d'URLS à partir d'un flux RSS
 *
 * @author Guillaume SAINTHILLIER
 */

abstract class AgendaParser implements ParserInterface
{
    /**
     * Url du site à parser.
     */
    protected $url;

    /**
     * Urls du site à parser.
     */
    protected $urls;

    /**
     * @var Site
     */
    protected $site;

    abstract public function getRawAgendas();

    public function addUrl($url)
    {
        $this->urls[] = $url;

        return $this;
    }

    public function getUrls()
    {
        return $this->urls;
    }

    public function setURL($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getURL()
    {
        return $this->url;
    }

    public function parse()
    {
        //Tableau des informations récoltées
        return $this->getRawAgendas();
    }

    protected function parseDate($date)
    {
        $tabMois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return preg_replace_callback("/(.+)(\d{2}) (" . implode('|', $tabMois) . ") (\d{4})(.*)/iu",
            function ($items) use ($tabMois) {
                return $items[4] . '-' . (array_search($items[3], $tabMois) + 1) . '-' . $items[2];
            }, $date);
    }
}
