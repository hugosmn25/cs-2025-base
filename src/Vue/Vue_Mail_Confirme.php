<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Mail_Confirme extends Vue_Composant
{
    public function __construct()
    {
    }

    function donneTexte(): string
    {
        $str= "<H1>Un mail de réinitialisation de votre mot de passe vous a été adressé !</H1> ";

        return $str;
    }

}