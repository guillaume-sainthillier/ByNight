<?php

/*
 * Effectue la gestion des différents parser
 */

namespace App\Parser\Manager;

use App\Parser\ParserInterface;
use App\Utils\Monitor;
use function array_merge;
use function count;
use function sprintf;

/**
 * @author Guillaume SAINTHILLIER
 */
class ParserManager
{
    protected $parsers;

    public function __construct()
    {
        $this->parsers = [];
    }

    public function add(ParserInterface $parser)
    {
        $this->parsers[] = $parser;

        return $this;
    }

    public function getAgendas()
    {
        $full_agendas = [];

        foreach ($this->parsers as $parser) {
            /*
             * @var ParserInterface $parser
             */
            Monitor::writeln(sprintf(
                'Lancement de <info>%s</info>',
                $parser->getNomData()
            ));
            $agendas = $parser->parse();

            if (count($this->parsers) > 1) {
                Monitor::writeln(sprintf(
                    '<info>%d</info> événements à traiter pour <info>%s</info>',
                    count($agendas),
                    $parser->getNomData()
                ));
            }

            foreach ($agendas as $agenda) {
                $agenda['from_data'] = $parser->getNomData();
            }

            $full_agendas = array_merge($full_agendas, $agendas);
        }

        Monitor::writeln(sprintf(
            '<info>%d</info> événements à traiter au total',
            count($full_agendas)
        ));

        return $full_agendas;
    }
}
