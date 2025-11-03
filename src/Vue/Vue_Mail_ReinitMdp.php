<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Mail_ReinitMdp extends Vue_Composant
{
    public function __construct()
    {
    }

    function donneTexte(): string
    {
        $str= "  
  <form action='/reinitmdpconfirm' method='post' style='width: 50%; display: block; margin: auto;'>
             
                <h1>Générer un mot de passe temporaire</h1>
                
                <label><b>Compte</b></label>
                <input type='email' placeholder='mail du compte à renouveler le mdp' name='email' required>
                
                <button type='submit'>
                            Recevoir un mot de passe temporaire
                </button>
  </form>
  <form action='/reinitmdptoken' method='post' style='width: 50%; display: block; margin: 2rem auto 0;'>
                <h1>Recevoir un lien de réinitialisation</h1>
                <label><b>Compte</b></label>
                <input type='email' placeholder='mail du compte à réinitialiser' name='email' required>
                <button type='submit'>
                            Recevoir un lien par e-mail
                </button>
  </form>
 <form action='/' method='get' style='width: 50%; display: block; margin: 2rem auto;'>
        <button type='submit'>
                            Retour au formulaire de connexion
        </button>
 </form>
    ";
        return $str;
    }
}
