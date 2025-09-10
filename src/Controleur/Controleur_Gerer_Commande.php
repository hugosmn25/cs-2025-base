<?php

namespace App\Controleur;

use App\Modele\Modele_Commande;
use App\Vue\Vue_Action_Sur_Commande_Entreprise;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Commande_Etat;
use App\Vue\Vue_Panier_Client;
use App\Vue\Vue_Commande_Histo;
use App\Vue\Vue_Commande_Info;
use App\Vue\Vue_Commande_Liste;
use App\Vue\Vue_Structure_Entete;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Gerer_Commande
{
    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    public function init(): void
    {
         
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));

        $listeEtatCommande = Modele_Commande::EtatCommande_Liste();
        $this->vue->addToCorps(new Vue_Commande_Etat($listeEtatCommande));
    }

    public function boutonCategorie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idEtatCommande = $args["idEtatCommande"];
        //On a demandé les commandes d'une catégorie
        $listeCommande = Modele_Commande::Commande_Select_Par_Etat($idEtatCommande);
        $this->vue->addToCorps(new Vue_Commande_Liste($listeCommande, "Gerer_Commande"));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function Toute(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeCommande = Modele_Commande::Commande_Select_Toute();
        $this->vue->addToCorps(new Vue_Commande_Liste($listeCommande, "Gerer_Commande"));
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
        $this->vue->addToCorps(new Vue_Action_Sur_Commande_Entreprise($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Info($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function Signaler_CommandePayee(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_REQUEST["info"]))
            $infoComplementaire = $_REQUEST["info"];
        else
            $infoComplementaire = "";
        $idCommande = $args["idCommande"];
        Modele_Commande::HistoriqueEtatCommande_Inserer($idCommande, 3, $infoComplementaire, -1, $_SESSION["idUtilisateur"]);
        
        $listeArticleCommande = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($idCommande);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($idCommande);
        $histoEtatCommande = Modele_Commande::Historique_Etat_Commande_Select_ParIdCommande($idCommande);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticleCommande, true, $infoCommande));
        $this->vue->addToCorps(new Vue_Action_Sur_Commande_Entreprise($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Info($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function Signalee_CommandeEnPreparation(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_REQUEST["info"]))
            $infoComplementaire = $_REQUEST["info"];
        else
            $infoComplementaire = "";
         $idCommande = $args["idCommande"];
        Modele_Commande::HistoriqueEtatCommande_Inserer($idCommande, 4, $infoComplementaire, -1, $_SESSION["idUtilisateur"]);
        $listeArticleCommande = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($idCommande);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($idCommande);
        $histoEtatCommande = Modele_Commande::Historique_Etat_Commande_Select_ParIdCommande($idCommande);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticleCommande, true, $infoCommande));
        $this->vue->addToCorps(new Vue_Action_Sur_Commande_Entreprise($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Info($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function Signalee_CommandeProblemeStock(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_REQUEST["info"]))
            $infoComplementaire = $_REQUEST["info"];
        else
            $infoComplementaire = "";
         $idCommande = $args["idCommande"];
        Modele_Commande::HistoriqueEtatCommande_Inserer($idCommande, 5, $infoComplementaire, -1, $_SESSION["idUtilisateur"]);
        $listeArticleCommande = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($idCommande);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($idCommande);
        $histoEtatCommande = Modele_Commande::Historique_Etat_Commande_Select_ParIdCommande($idCommande);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticleCommande, true, $infoCommande));
        $this->vue->addToCorps(new Vue_Action_Sur_Commande_Entreprise($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Info($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function Signalee_CommandeEnvoyée(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_REQUEST["info"]))
            $infoComplementaire = $_REQUEST["info"];
        else
            $infoComplementaire = "";
        $idCommande = $args["idCommande"];
        Modele_Commande::HistoriqueEtatCommande_Inserer($idCommande, 6, $infoComplementaire, -1, $_SESSION["idUtilisateur"]);
        $listeArticleCommande = Modele_Commande::Commande_Avoir_Article_Select_ParIdCommande($idCommande);
        $infoCommande = Modele_Commande::Commande_Select_ParIdCommande($idCommande);
        $histoEtatCommande = Modele_Commande::Historique_Etat_Commande_Select_ParIdCommande($idCommande);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticleCommande, true, $infoCommande));
        $this->vue->addToCorps(new Vue_Action_Sur_Commande_Entreprise($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Info($infoCommande));
        $this->vue->addToCorps(new Vue_Commande_Histo($histoEtatCommande));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
