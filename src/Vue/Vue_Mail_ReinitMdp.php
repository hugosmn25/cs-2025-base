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
  <form action='/reinitmdpconfirm' method='post' style='    width: 50%;    display: block;    margin: auto;'>
             
                <h1>Mail à renouveler</h1>
                
                <label><b>Compte</b></label>
                <input type='email' placeholder='mail du compte à renouveler le mdp' name='email' required>
                
                <button type='submit'  >
                            Renouveler mdp
                </button>";
        $str .=  "
 </form>
    ";
        return $str;
    }
}