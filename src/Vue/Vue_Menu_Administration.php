<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Menu_Administration extends Vue_Composant
{
    public function __construct()
    {
    }
    function donneTexte(): string
    {
        return "
             <nav id='menu'>
              <ul id='menu-closed'> 
                <li><a href='/Gerer_utilisateur?'>Utilisateurs</a></li>
                     
               
                        <li><a href='/Gerer_catalogue?'>Catalogue</a></li>   
            
          <li><a href='/Gerer_Commande?'>Commandes</a></li>
            
                <li><a href='/Gerer_monCompte?'>Mon compte</a></li> 
               </ul>
            </nav> ";






    }
}
