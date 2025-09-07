<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Liste_Categorie  extends Vue_Composant
{
    private array $listeCategorie;
    public function __construct(array $listeCategorie)
    {
        $this->listeCategorie=$listeCategorie;
    }

    function donneTexte(): string
    {
        $str= "
    <h1>Liste des Catégories de produit</h1> <br>
    <table style='    display: inline-block;'>
        <form action='/Gerer_catalogue/nouvelleCategorie' method='get'>
            
            <td colspan='4'>
            <button class='btnRadius'  type='submit'  >
                             Nouvelle Catégorie ?
            </button> 
            
            </td> 
        </form>
            <tr>
                <th>ID Catégorie</th>
                <th>Catégorie</th>
            </tr>";
        $i=0;
        while ($i < count($this->listeCategorie)) {
            $iemeCategorie=$this->listeCategorie[$i];
            $str .=  "
            <tr>
                <input type='hidden' name='idCategorie' value='$iemeCategorie[idCategorie]'>
                
                <td>$iemeCategorie[idCategorie]</td>
                <td>$iemeCategorie[libelle]</td>
                <td>
                    <form action='/Gerer_catalogue/ModifierCategorie/$iemeCategorie[idCategorie]' method='get' style='display: contents'>
                         
                             
                              
                         <button class='btnRadius'  type='submit'  >
                          Modifier
                          </button>
                    </form>
                </td>
                
            ";
            if ($iemeCategorie["desactiverCategorie"] == 0) {
                $str .=  "<td>
                <form  action='/Gerer_catalogue/DesactiverCategorie/$iemeCategorie[idCategorie]' method='get' style='display: contents'>
                 
                         
                              
                            <button class='btnRadius'  type='submit'  >
                                Désactiver
                             </button>
                       </form>
                  </td>";
            } elseif ($iemeCategorie["desactiverCategorie"] == 1) {
                $str .=  "<td>
                        <form action='/Gerer_catalogue/ActiverCategorie/$iemeCategorie[idCategorie]' method='get'  style='display: contents'>
                             
                             
                             
                            <button class='btnRadius'  type='submit'  >
                                Activer
                             </button>
                       </form>
                  </td>";
            }
            $str .=  "</tr>";
            $i++;
        }
        $str .=  "</table>";
        return $str;
    }
}