#!/bin/php
<?php

include \realpath(__DIR__."/Gltm.class.php");

/**
 * Fonction application
 */
function gltm($argc, $argv)
{
    // Rejeter le traitement si il n'y pas le bon nombre de paramètres.
    if ($argc !== 2) {
        echo "Usage: ".$argv[0]." FILE\n";
        die(1);
    }

    // Identification du chemin absolu du fichier
    $file = !\preg_match('/^\//', $argv[1]) ? \realpath(\substr(\shell_exec('pwd'), 0, -1).'/'.$argv[1]) : $argv[1];

    // Le fichier n'exite pas
    if (!$file) {
        echo "File doesn't exist";
        die(1);
    }

    try {

        // Génération de la table des matières
        $gltm = new \Gltm\Gltm($file);

        // Sauvegarde deja existante
        if (file_exists($file."~")) {
            throw new \Exception('Backup already exist, clean it before regenerate TOC');
        }

        // Sauvegarde du fichier existant
        if (!copy ( $file , $file."~")) {
            throw new \Exception('Can\'t do backup');
        }

        // Enregistre le contenu avec la table des matières à jour
        if (file_put_contents($file, $gltm->getContentWithToc()) === false) {
            throw new \Exception('Can\'t generate TOC');
        }

        echo "File with TOC generated\n";

    } catch (\Exception $e) {

        // En cas d'erreur
        echo 'ERROR : '.$e->getMessage()."\n";
        die(1);
    }
}

gltm($argc, $argv);