<?php

namespace App\Vue;
use App\Modele\Modele_Commande;
use App\Utilitaire\Vue_Composant;
use function App\Fonctions\Facture_DonneRefVirementAttendu;

class Facture_BasPageBrulerie extends Vue_Composant
{
    private array $infoCommande;
    private array $infoEntreprise;


    public function __construct($infoCommande, $infoEntreprise)
    {
        $this->infoCommande=$infoCommande;
        $this->infoEntreprise=$infoEntreprise;
    }

    function donneTexte(): string
    {
        return "
        <H3 align='right' > Nous vous remercions pour votre commande .</H3>
    <br >
    <br >
    <h4 align='center' > Votre commande sera validée à la reception de votre virement dans un délai de 30 jours . 
    <br > Référence de virement : " . Facture_DonneRefVirementAttendu($this->infoCommande, $this->infoEntreprise) . " </h4 >

        ";
    }
}