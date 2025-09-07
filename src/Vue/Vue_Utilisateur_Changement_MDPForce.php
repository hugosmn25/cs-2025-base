<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Utilisateur_Changement_MDPForce extends Vue_Composant
{

    private string $action;
    private string $msg;

    private string $case;

    function __construct(string $msg="", string $case="visiteur")
    {
        $this->msg=$msg;
        $this->case=$case;

    }

    function donneTexte(): string
    {

        $str="    <form action='/$this->case/submitModifMDPForce' method='post' style='display: contents'>
<table style='display: inline-block'>  
        
        <h1>Changement Mot de passe obligatoire</h1>
        <tr> <td><label>Veuillez saisir votre nouveau mot de passe : </label></td>
            <td><input type='password' placeholder='mot de passe' name='NouveauPassword' required></td> </tr>
        <tr><td><label>Veuillez confirmer votre nouveau mot de passe : </label></td>
            <td><input type='password' placeholder='mot de passe' name='ConfirmPassword' required></td></tr>
        <tr> <td><button type='submit'>Modifier son mot de passe</button></td></tr>
    </form>
    <div>$this->msg</div>";
        return $str;
    }
}
