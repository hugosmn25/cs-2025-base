<?php

namespace App\Controleur;

use App\Modele\Modele_Catalogue;
use App\Modele\Modele_Commande;
use App\Vue\Vue__CategoriesListe;
use App\Vue\Vue_Categories_Liste;
use App\Vue\Vue_Menu_Entreprise_Salarie;
use App\Vue\Vue_Produits_Info_Clients;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
/**
 * Contrôleur pour la gestion des catégories
 */
class Controleur_Catalogue_client
{

    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
       
    }

    public function init(): void
    {
         $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);

        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));

        //Vue_Entreprise_Client_ Menu();
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $this->vue->addToCorps(new Vue_Categories_Liste($listeCategorie, false));


    }


    /*    if ($action == "AjoutPanierClient") {
            //on met dans le panier avant de calculer le menu
            Modele_Commande::Panier_Ajouter_Produit_ParIdProduit($_SESSION["idEntreprise"], $_REQUEST["idProduit"]);
        }*/

    public function boutonCategorie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idCategorie = $args["idCategorie"];
        $listeProduit = Modele_Catalogue::Select_Produit_Select_ParIdCateg($idCategorie, "client");
        $this->vue->addToCorps(new Vue_Produits_Info_Clients($listeProduit, $idCategorie));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
    public function okRechercher(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $produits_recherche = Modele_Catalogue::Produit_Rechercher($_REQUEST["recherche"], "client");
        $this->vue->addToCorps(new Vue_Produits_Info_Clients($produits_recherche, null, $_REQUEST["recherche"]));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
    public function AjoutPanierClient(Request $request, Response $response, array $args): Response
    {
       
        $idProduit = $args["idProduit"];
        Modele_Commande::Panier_Ajouter_Produit_ParIdProduit( $_SESSION["idEntreprise"],$idProduit );
         $this->init();
        //
        if ($_REQUEST["idCategorie"] != "") {
            $listeProduit = Modele_Catalogue::Select_Produit_Select_ParIdCateg($_REQUEST["idCategorie"], "client");
            $idCategorie = $_REQUEST["idCategorie"];
            $recherche = null;
        } elseif ($_REQUEST["recherche"] != "") {
            $listeProduit = Modele_Catalogue::Produit_Rechercher($_REQUEST["recherche"], "client");
            $idCategorie = null;
            $recherche = $_REQUEST["recherche"];
        }
        $this->vue->addToCorps(new Vue_Produits_Info_Clients($listeProduit, $idCategorie, $recherche));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
        //$_SESSION["idEntreprise"]
    }
    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();

        $listeProduit = Modele_Catalogue::Produits_Select_Libelle_Categ("client");
        $this->vue->addToCorps(new Vue_Produits_Info_Clients($listeProduit));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}


