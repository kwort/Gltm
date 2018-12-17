<?php

namespace Gltm;

class Gltm
{
    /// Chemin du fichier
    private $filepath;

    /// Contenu brut du fichier
    private $content;

    /// Contenu du fichier decoupé par ligne
    private $contentLines;

    /// Ligne du debut de la table des matières
    private $startToc;

    /// Ligne du fin de la table des matières
    private $endToc;

    /// Table de matière
    private $tableOfContent;

    /**
     * Depuis le chemin absolu du fichier, extrait le contenu, le decoupe en ligne
     * et determine la zone de la table des matières
     */
    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
        $this->content = \file_get_contents($filepath);
        $this->contentLines = explode($this->getCR(), $this->content);
        $this->startToc = null;
        $this->endToc = null;
        $this->genererateToc();
    }

    /**
     * Renvoi la table des matières
     */
    public function getTableOfContent()
    {
        return implode($this->getCR(), $this->tableOfContent);
    }

    /**
     * Renvoi le contenu avec la table des matières généré
     */
    public function getContentWithToc()
    {
        $header = array_slice($this->contentLines, 0, $this->startToc);
        $content = array_slice($this->contentLines, ($this->endToc - 1));
        $result = array_merge($header, [''], $this->tableOfContent, [''], $content);

        return implode($this->getCR(), $result);
    }

    /**
     * Transforme un titre en slug gitlab
     */
    private function slugify($string, $replace = array(), $delimiter = '-')
    {
        if (!extension_loaded('iconv')) {
          throw new \Exception('iconv module not loaded');
        }
        // Save the old locale and set the new locale to UTF-8
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');
        $clean = $string;
        if (!empty($replace)) {
          $clean = str_replace((array) $replace, ' ', $clean);
        }
        $clean = preg_replace("/[^a-zA-Z0-9àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸç\/_|+ -]/", '', $clean);
        $clean = strtolower($clean);
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean, $delimiter);
        // Revert back to the old locale
        setlocale(LC_ALL, $oldLocale);
        return $clean;
    }

    /**
     * Determine le carried retrun utilisé
     */
    private function getCR()
    {
        $nb_crlf = \substr_count($this->content, "\r\n"); 
        $nb_lf = \substr_count($this->content, "\n");
        $cr = "";

        if ( $nb_crlf == 0 && ($nb_crlf < $nb_lf) ) {
            $cr = "\n";
        } else if ( $nb_crlf === $nb_lf ) {
            $cr = "\r\n";
        } else {
            throw new \Exception('Can\'t determine cariedge retrun');
        }

        return $cr;
    }

    /**
     * Génère la table des matières
     */
    public function genererateToc()
    {
        $toc = [];
        $titles = [];
        $level = 0;
        $pre = false;
        
        foreach ($this->contentLines as $lineNumber => $line) {

            if (preg_match('/^```/', $line)) {
                $pre = !$pre;
            }

            if ($pre) {
                continue;
            }

            if (preg_match('/^(#{1,}) (.*)/', $line, $matches)) {

                $subLevel = strlen($matches[1]);

                if (isset($titles[$matches[2]])) {
                    throw new \Exception('Duplicate titles "'.$matches[2].'"');
                } else {
                    $titles[$matches[2]] = true;
                }

                if ($subLevel > ($level + 1)) {
                    throw new \Exception('Bad level title, "'.$matches[0].'" after "'.$previous.'"');
                }

                if ($subLevel == ($level + 1)) {

                    if ($level == 0) {
                        $this->startToc = $lineNumber + 1;
                    }

                    if ($level == 1) {
                        $this->endToc = $lineNumber + 1;
                    }

                    $level += 1;

                } else if ($subLevel !== $level) {

                    $level -= ($level - $subLevel);

                    if ($level <= 0) {
                        throw new \Exception('Only one Title1');
                    }
                    
                } else {
                    
                }

                if ($level > 1) {
                    $toc []= str_repeat('  ', ($level-2))."- [".$matches[2]."](#".$this->slugify($matches[2]).")";
                }
            }
        }

        $this->tableOfContent = $toc;
    }
}