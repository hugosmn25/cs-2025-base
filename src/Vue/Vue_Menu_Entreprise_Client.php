<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Menu_Entreprise_Client extends Vue_Composant
{
    public function __construct()
    {
    }

    function donneTexte(): string
    {
        return "
<nav id='menu'>
  <ul id='menu-closed'>  
    <li><a href='/Gerer_Entreprise?'>Compte d'entreprise</a></li> 
    
  </ul>
</nav> ";
    }
}

