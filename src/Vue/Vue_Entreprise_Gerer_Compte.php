<?php

namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Entreprise_Gerer_Compte extends Vue_Composant
{
    private string $msg;

    public function __construct(string $msg = "")
    {
        $this->msg = $msg;
    }

    public function donneTexte(): string
    {
        return "
    <h1>Gestion du compte</h1>
    <table style='display: inline-block'>
        <tr>
            <td>
                <form action='/Gerer_Entreprise/infoEntreprise' method='get' style='display: contents'>
                    <button type='submit'>Les informations de l&apos;entreprise</button>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/Gerer_Entreprise/salariesHabitites' method='get' style='display: contents'>
                    <button type='submit'>Personnes habilitees</button>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/Gerer_Entreprise/ChangerMDPEntreprise' method='get' style='display: contents'>
                    <button type='submit'>Changer mot de passe</button>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/Gerer_Entreprise/gerer2FA' method='get' style='display: contents'>
                    <button type='submit'>Configurer le deuxieme facteur</button>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/Gerer_monCompte/deconnexionEntreprise' method='get' style='display: contents'>
                    <button type='submit'>Se deconnecter</button>
                </form>
            </td>
        </tr>
    </table>
    <br>$this->msg";
    }
}
