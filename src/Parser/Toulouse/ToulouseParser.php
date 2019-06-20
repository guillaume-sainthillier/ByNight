<?php

namespace App\Parser\Toulouse;

use App\Entity\Event;
use App\Parser\EventParser;
use DateTime;
use ForceUTF8\Encoding;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Description of ToulouseParser.
 *
 * @author guillaume
 */
class ToulouseParser extends EventParser
{
    public function __construct()
    {
        $this->setURL('https://data.toulouse-metropole.fr/explore/dataset/agenda-des-manifestations-culturelles-so-toulouse/download/?format=csv&timezone=Europe/Berlin&use_labels_for_header=true');
    }

    public function getRawEvents()
    {
        $fichier = $this->downloadCSV();
        if (null !== $fichier) {
            return $this->parseCSV($fichier);
        }

        return [];
    }

    /**
     * @param string $fichier le chemin absolu vers le fichier
     *
     * @return Event[] les events parsés
     */
    protected function parseCSV($fichier)
    {
        $tab_events = [];

        $fic = \fopen($fichier, 'r');
        \fgetcsv($fic, 0, ';', '"', '"'); //Ouverture de la première ligne

        while ($cursor = \fgetcsv($fic, 0, ';', '"', '"')) {
            $tab = \array_map(function ($e) {
                return Encoding::toUTF8($e);
            }, $cursor);

            if ($tab[1] || $tab[2]) {
                $nom = $tab[1] ?: $tab[2];

                $date_debut = new DateTime($tab[5]);
                $date_fin = new DateTime($tab[6]);

                $tab_events[] = [
                    'external_id' => 'TOU-' . $tab[0],
                    'nom' => $nom,
                    'descriptif' => $tab[4],
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'horaires' => $tab[7],
                    'modification_derniere_minute' => $tab[9],
                    'placeName' => $tab[10],
                    'placeStreet' => $tab[12],
                    'latitude' => (float) $tab[20] ?: null,
                    'longitude' => (float) $tab[21] ?: null,
                    'placePostalCode' => $tab[14],
                    'placeCity' => $tab[15],
                    'placeCountryName' => 'France',
                    'type_manifestation' => $tab[16],
                    'categorie_manifestation' => $tab[17],
                    'theme_manifestation' => $tab[18],
                    'reservation_telephone' => $tab[22],
                    'reservation_email' => $tab[23],
                    'reservation_internet' => $tab[24],
                    'tarif' => $tab[26],
                    'source' => 'https://data.toulouse-metropole.fr/explore/dataset/agenda-des-manifestations-culturelles-so-toulouse/information/',
                ];
            }
        }

        return $tab_events;
    }

    /**
     * Télécharge un fichier CSV sur le repertoire TEMP depuis l'URI de l'Open Data Toulouse.
     *
     * @return string le chemin absolu vers le fichier
     */
    protected function downloadCSV()
    {
        $data = \file_get_contents($this->getURL());
        $path_file = \sprintf('%s/data_manifestations/agenda.csv', \sys_get_temp_dir());
        $fs = new Filesystem();
        $fs->dumpFile($path_file, $data);

        return $path_file;
    }

    public function getNomData()
    {
        return 'Toulouse';
    }
}
