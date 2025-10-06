<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Menu_Administration extends Vue_Composant
{
    private int $idCategorie_Utilisateur;
    public function __construct(int $idCategorie_Utilisateur)
    {
        $this->idCategorie_Utilisateur = $idCategorie_Utilisateur;
    }
    function donneTexte(): string
    {
        switch( $this->idCategorie_Utilisateur ) {
            case 1: // Administrateur
                 return "
             <nav id='menu'>
              <ul id='menu-closed'> 
                <li><a href='/Gerer_utilisateur?'>Utilisateurs</a></li>
                <li><a href='/Gerer_monCompte?'>Mon compte</a></li> 
               </ul>
            </nav> ";
            case 2: // Gestionnaire catalogue
                 return "
             <nav id='menu'>
              <ul id='menu-closed'> 
                <li><a href='/Gerer_catalogue?'>Catalogue</a></li> 
                <li><a href='/Gerer_monCompte?'>Mon compte</a></li> 
              </ul>
            </nav> ";
            case 5: // Commercial
                 return "
             <nav id='menu'>
              <ul id='menu-closed'>                 
                <li><a href='/Gerer_Commande?'>Commandes</a></li>
                <li><a href='/Gerer_monCompte?'>Mon compte</a></li> 
               </ul>
            </nav> ";
            case 6: // Gestionnaire RGPD
                 return "
             <nav id='menu'>
              <ul id='menu-closed'> 
                <li><a href='/Admin_Rgpd'>RGPD</a></li>
                <li><a href='/Gerer_monCompte?'>Mon compte</a></li> 
               </ul>
            </nav> ";
            default:
                return ""; // Menu vide pour les autres catégories
        }
    }
}
