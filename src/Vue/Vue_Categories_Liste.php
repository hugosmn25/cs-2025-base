<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Categories_Liste extends Vue_Composant
{
    private array $listeCategorie;
    private bool $gestion=true;
    public function __construct(array $listeCategorie, bool $gestion=true)
    {
        $this->listeCategorie=$listeCategorie;
        $this->gestion=$gestion;
    }

    function donneTexte(): string
    {
        $str="";
        $i=0;
        $str .= "<nav id='categorie'>
                <ul id='menu-closed'>
                ";
        if ($this->gestion) {
            $str .= "<form action='/Gerer_catalogue/AjouterCategorie' method='get' style='display: contents'> 
                        
                     <li> 
                    
                         <button type='submit' >+</button> 
                    </li>
                </form>";
        }


        while ($i < count($this->listeCategorie)) {
            $iemeCategorie=$this->listeCategorie[$i];
            if ($iemeCategorie["desactiverCategorie"] == 0) {
                $str .= "
                   <li>
                        <form action='/";
                        if($this->gestion)
                            $str .= "Gerer_catalogue";
                        else
                            $str .= "Catalogue_client";
                $str .= "/boutonCategorie/$iemeCategorie[idCategorie]' style='display: contents' method='get'> 
                             
                             
                            <button type='submit' >
                                $iemeCategorie[libelle]
                            </button>
                        </form>
                   </li> 
                   ";
            }
            $i++;
        }
        $str .= "
                <form action='/Gerer_catalogue/okRechercher' method='get' style='display: contents'> 
                    
                    <li><input type='text' name='recherche' placeholder='Rechercher'> </li>
                        
                        
                    <li>
                                <button type='submit'>OK</button>
                     </li>
                </form>";

        $str .= "
            </ul>
            </nav>";

        return $str;
    }
}