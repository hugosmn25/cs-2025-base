<?php

namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Compte_Administration_Gerer extends Vue_Composant
{
    private string $msg;
    private string $action;

    public function __construct(string $msg = "", string $action = "Gerer_monCompte")
    {
        $this->msg = $msg;
        $this->action = $action;
    }

    public function donneTexte(): string
    {
        return "
    <h1>Gerer mon compte</h1>
    <table style='display: inline-block'>
        <tr>
            <td>
                <form action='/$this->action/changerMDP' method='get' style='display: contents'>
                    <button type='submit'>Changer mot de passe</button>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/$this->action/gerer2FA' method='get' style='display: contents'>
                    <button type='submit'>Configurer mon deuxieme facteur</button>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/$this->action/SeDeconnecter' method='get' style='display: contents'>
                    <button type='submit'>Se deconnecter</button>
                </form>
            </td>
        </tr>
    </table>
    $this->msg
    ";
    }
}
