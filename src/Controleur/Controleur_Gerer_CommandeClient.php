<?php

namespace App\Controleur;

use App\Modele\Modele_Commande;
use App\Modele\Modele_Entreprise;
use App\Vue\Facture_BasPageBrulerie;
use App\Vue\Facture_EnteteBrulerie;
use App\Vue\Vue_Action_Sur_Commande_Client;
use App\Vue\Vue_Panier_Client;
use App\Vue\Vue_Afficher_Button_Facture;
use App\Vue\Vue_Commande_Info_Entreprise;
use App\Vue\Vue_Commande_Histo;
use App\Vue\Vue_Menu_Entreprise_Salarie;
use App\Vue\Vue_Structure_Entete;
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Gerer_CommandeClient
{
    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    public function init(): void
    {
        // Pas PDF => Génération entête HTML (logique par défaut non-PDF)
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));
    }

    public function AfficherCommandePDF(Request $request, Response $response, array $args): Response
    {
        //$this->init();
        // Le pdf n'a pas besoin des entêtes HTML
        ob_start(); // activation de la redirection HTML vers une variable
        $listeArticlePanier = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($_REQUEST["idCommande"]);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($_REQUEST["idCommande"]);
        $infoEntreprise = Modele_Entreprise::Entreprise_Select_ParId($_SESSION["idEntreprise"]);
        $this->vue->setEntete(new Facture_EnteteBrulerie($infoCommande, $infoEntreprise));
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticlePanier, true));
        $this->vue->setBasDePage(new Facture_BasPageBrulerie($infoCommande, $infoEntreprise));
        echo $this->vue->donneStr();
        $content = ob_get_clean(); // Fin de la redirection et transfert du HTML capturé vers un fichier
        $html2pdf = new Html2Pdf('L', 'A4', 'fr');
        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->writeHTML($content);
        $html2pdf->output('facture.pdf');
        exit();
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function VoirDetailCommande(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idCommande = $args["idCommande"];
        $listeArticleCommande = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($idCommande);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($idCommande);

        $histoEtatCommande = Modele_Commande::Historique_Etat_Commande_Select_ParIdCommande($idCommande);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticleCommande, true, $infoCommande));
        $this->vue->addToCorps(new Vue_Afficher_Button_Facture($idCommande));

        if ($histoEtatCommande != null && $histoEtatCommande != false) {
            $etatAct = $histoEtatCommande[0];

            $this->vue->addToCorps(new Vue_Action_Sur_Commande_Client($infoCommande, $etatAct));
            $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function Signalee_CommandeReceptionnee(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_REQUEST["info"]))
            $infoComplementaire = $_REQUEST["info"];
        else
            $infoComplementaire = "";
        Modele_Commande::HistoriqueEtatCommande_Inserer($_REQUEST["idCommande"], 7, $infoComplementaire, $_SESSION["idSalarie"]);
        $listeArticleCommande = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($_REQUEST["idCommande"]);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($_REQUEST["idCommande"]);
        $histoEtatCommande = Modele_Commande::Historique_Etat_Commande_Select_ParIdCommande($_REQUEST["idCommande"]);
        $etatAct = $histoEtatCommande[0];
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticleCommande, true, $infoCommande));
        $this->vue->addToCorps(new Vue_Action_Sur_Commande_Client($infoCommande, $etatAct));
        $this->vue->addToCorps(new Vue_Afficher_Button_Facture($_REQUEST["idCommande"]));
        $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function Signalee_CommandeReceptionneeIncident(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_REQUEST["info"]))
            $infoComplementaire = $_REQUEST["info"];
        else
            $infoComplementaire = "";

        Modele_Commande::HistoriqueEtatCommande_Inserer($_REQUEST["idCommande"], 8, $infoComplementaire, $_SESSION["idSalarie"]);
        $listeArticleCommande = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($_REQUEST["idCommande"]);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($_REQUEST["idCommande"]);
        $histoEtatCommande = Modele_Commande::Historique_Etat_Commande_Select_ParIdCommande($_REQUEST["idCommande"]);
        $etatAct = $histoEtatCommande[0];
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticleCommande, true, $infoCommande));
        $this->vue->addToCorps(new Vue_Action_Sur_Commande_Client($infoCommande, $etatAct));
        $this->vue->addToCorps(new Vue_Afficher_Button_Facture($_REQUEST["idCommande"]));
        $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeCommande = Modele_Commande::Commande_Select_ParIdEntreprise($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Commande_Info_Entreprise($listeCommande, false));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
