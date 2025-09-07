<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Connexion_Formulaire_client extends Vue_Composant
{
    private string $msgErreur;
    public function __construct(string $msgErreur ="")
    {
        $this->msgErreur=$msgErreur;
    }

    function donneTexte(): string
    {
        $str= "
<h1>Café : Connexion</h1>
<div  style='    width: 50%;    display: block;    margin: auto;'>  
  <form action='/visiteur/SeConnecter' method='post'>
  
                <h1>Connexion</h1>
                
                <label><b>Compte</b></label>
                <input type='text' placeholder='identifiant du compte' name='compte' required>

                <label><b>Mot de passe</b></label>
                <input type='password' placeholder='mot de passe' name='password' required>
                
                <button type='submit'  >
                    Se connecter
                </button>                
                " ;
        if($this->msgErreur != "")
        {
            $str .=  " <label><b>Erreur : $this->msgErreur</b></label>";
        }

        $str .=  "

</form>
<form action='/reinitmdp' method='get'>

<h1>Mot de passe perdu ?</h1>

<button type='submit'  > 
    Réinitialiser le mdp
</button>
";

        $str .= "
</form>
</div>
    ";


        return $str ;
    }
}