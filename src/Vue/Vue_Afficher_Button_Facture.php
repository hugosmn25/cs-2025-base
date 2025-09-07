<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Afficher_Button_Facture extends Vue_Composant
{
    private int $idCommande;
    public function __construct(int $idCommande)
    {
        $this->idCommande=$idCommande;
    }

    function donneTexte(): string
    {
        return "<form action='/Gerer_CommandeClient/AfficherCommandePDF/$this->idCommande' style='display: contents' method='get'  >
                        
                         
                    
                        <input type='hidden' name='idCommande' value='$this->idCommande' >
                        <button type='submit'  >
                            Voir facture
                        </button>
         </form >";
    }
}