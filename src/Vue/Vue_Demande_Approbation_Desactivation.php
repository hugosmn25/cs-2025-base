<?php

namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Demande_Approbation_Desactivation  extends Vue_Composant
{
    private string $idCategorie;
    private string $nomCategorie;
    public function __construct(string $idCategorie ="", string $nomCategorie="")
    {
        $this->idCategorie=$idCategorie;
        $this->nomCategorie=$nomCategorie;
    }

    function donneTexte(): string
    {

        return   " <table style='margin: auto'>
            <h3> Etes-vous sûr(e) de vouloir désactiver la catégorie \"$this->nomCategorie\" ?
             <br> Si oui, les produits se trouvant à l'intérieur de celle-ci ne seront plus visibles sur le catalogue client.</h3>
                
        
                
                <td style='width: 100px; height: 100px;'>
                <form action='/Gerer_catalogue/OuiDesactivation/$this->idCategorie' method='get' style='display: contents; align-content: center'>
                    
                    <button type='submit'  >Oui</button>
               </form>
                    </td>
                <td style='width: 100px; height: 100px;'>
                <form action='/Gerer_catalogue/AnnulerDesactivation/$this->idCategorie' style='display: contents; align-content: center'>
                    
                    <button type='submit'  >Annuler</button>
                </form>
                    </td>
            </form>
            </table>
 ";
    }
}