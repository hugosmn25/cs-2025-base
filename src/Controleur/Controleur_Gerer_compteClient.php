<?php

namespace App\Controleur;

use App\Modele\Modele_Commande;
use App\Modele\Modele_Entreprise;
use App\Vue\Vue_Utilisateur_Changement_MDP;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Entreprise_Client;
use App\Vue\Vue_Entreprise_Gerer_Compte;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Gerer_compteClient
{
    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    public function init(): void
    {
        $this->vue->setEntete(new Vue_Structure_Entete());
    }

    public function ChangerMDPEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function submitModifMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //il faut récuperer le mdp en BDD et vérifier qu'ils sont identiques
        $entreprise_connectee = Modele_Entreprise::Entreprise_Select_ParId($_SESSION["idEntreprise"]);
        if (password_verify($_REQUEST["AncienPassword"], $entreprise_connectee["motDePasse"])) {
            //on vérifie si le mot de passe de la BDD est le même que celui rentré
            if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
                //Utilisateur_Modifier_motDePasse(  $_SESSION["idEntreprise"], $_REQUEST["NouveauPassword"] );
                $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
                $this->vue->addToCorps(new Vue_Entreprise_Gerer_Compte());
                // Dans ce cas les mots de passe sont bons, il est donc modifié
                $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Votre mot de passe a bien été modifié</b></label>"));
            } else {
                $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP());
                $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Les nouveaux mots de passe ne sont pas identiques</b></label>"));
            }
        } else {
            $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP());
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Vous n'avez pas saisi le bon mot de passe</b></label>"));
        }
        $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function deconnexionEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //L'utilisateur a cliqué sur "se déconnecter"
        session_destroy();
        unset($_SESSION["idEntreprise"]);
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function infoEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function salariesHabitites(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Cas par défaut: affichage du menu des actions.
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $this->vue->addToCorps(new Vue_Entreprise_Gerer_Compte());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
