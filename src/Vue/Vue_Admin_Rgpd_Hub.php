<?php
namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Admin_Rgpd_Hub extends Vue_Composant
{
    function donneTexte(): string
    {
        return "
        <section style='padding: 10px;'>
            <h1>Gestion RGPD</h1> 
             
                    <form action='/Admin_Rgpd/finalites' method='get' style='display: contents'>
                        <button type='submit'>Finalités</button>
                    </form>
                    — gérer la liste (ajouter, renommer, activer/désactiver)
                
                    <form action='/Admin_Rgpd/politique' method='get' style='display: contents'>
                        <button type='submit'>Politique</button>
                    </form>
                    — ajouter une nouvelle version de la politique
                 
                    <form action='/Admin_Rgpd/historique' method='get' style='display: contents'>
                        <button type='submit'>Historique</button>
                    </form>
                    — consulter les événements de consentement
                 
        </section>
        ";
    }
}
