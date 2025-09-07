<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_AfficherMessage extends Vue_Composant
{
    private string $msg;
    public function __construct(string $msg)
    {
        $this->msg=$msg;
    }

    function donneTexte(): string
    {
        return $this->msg;
    }

}