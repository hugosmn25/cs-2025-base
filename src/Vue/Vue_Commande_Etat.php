<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Commande_Etat extends Vue_Composant
{
    private array $listeEtatCommande;
    public function __construct(array $listeEtatCommande)
    {
        $this->listeEtatCommande=$listeEtatCommande;
    }

    function donneTexte(): string
    {
        $str="";
        $i=0;
        $str .=  "<nav id='etatCommande'>
                <ul id='menu-closed'>
                ";

        $str .=  "
                <form action='/Gerer_Commande/Toute' method ='get' style='display: contents'>      
                    
                    <li><button type='submit' >Toutes</button> </li>
                </form>";

        while ($i < count($this->listeEtatCommande)) {
            $iemeEtatCommande=$this->listeEtatCommande[$i];

            $str .=  "
                   <li>
                        <form action='/Gerer_Commande/boutonCategorie/$iemeEtatCommande[idEtatCommande]' method='get' style='display: contents'> 
                             
                             
                            <button type='submit' name='action' value=''> $iemeEtatCommande[libelle]</button>
                        </form>
                   </li> 
                   ";

            $i++;
        }
        $str .=  "
                <form action='/Gerer_Commande/okRechercher' method='post' style='display: contents'> 
                     
                    
                    <li><input type='text' name='recherche' placeholder='Rechercher'> </li>
                    <li><button type='submit' >OK</button> </li>
                </form>";
        $str .=  "
            </ul>
            </nav>";
        return $str;
    }

}