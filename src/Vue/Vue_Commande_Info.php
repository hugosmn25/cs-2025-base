<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Commande_Info extends Vue_Composant
{
    private array $infoCommande;
    public function __construct(array $infoCommande)
    {
        $this->infoCommande=$infoCommande;
    }

    function donneTexte(): string
    {
        return "";
    }

}