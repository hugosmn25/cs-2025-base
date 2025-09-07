<?php

namespace App\Utilitaire;
class Vue
{

    private ?Vue_Composant $entete = NULL;
    private ?Vue_Composant $menu = NULL;
    private  array  $corps = [];
    private ?Vue_Composant $basDePage = NULL;
    private bool $modeDebug = FALSE;

    public function setEntete(Vue_Composant $composant) : void
    {
        $this->entete = $composant;
    }

    public function setMenu(Vue_Composant $composant) : void
    {
        $this->menu = $composant;
    }

    public function addToCorps(Vue_Composant $composant) : void
    {
        $this->corps[] = $composant;
    }
    public function setBasDePage(Vue_Composant $composant) : void
    {
        $this->basDePage = $composant;
    }

    public function setDebut() : void
    {
        $this->modeDebug = true;
    }

    /**
     * Affiche le contenu de la vue
     * Le mode debug permet de ne pas afficher
     * @return void
     */
    public function donneStr() : string{
        $str = "";
        if(!$this->modeDebug) {
            if (!is_null($this->entete)) {
                $str .= $this->entete->donneTexte();
            }

            if (!(is_null($this->menu))) {
                $str .=  $this->menu->donneTexte();
            }

            //On affiche tous les élements contenus dans le corps
            foreach ($this->corps as $elementCorps)
                $str .=  $elementCorps->donneTexte();

            if (!(is_null($this->basDePage))) {
                $str .=  $this->basDePage->donneTexte();
            }
        }
        return $str;
    }
}