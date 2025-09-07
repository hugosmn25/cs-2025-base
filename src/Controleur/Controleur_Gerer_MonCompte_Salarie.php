<?php

namespace App\Controleur;

use App\Modele\Modele_Commande;
use App\Modele\Modele_Salarie;
use App\Vue\Vue_Compte_Administration_Gerer;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Entreprise_Salarie;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Vue\Vue_Utilisateur_Changement_MDP;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Gerer_MonCompte_Salarie
{
    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    public function init(): void
    {
        // Aucun code avant le switch dans la version initiale
    }

    public function changerMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Il a cliqué sur changer Mot de passe. Cas pas fini
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));
        $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("", "Gerer_MonCompte_Salarie"));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function submitModifMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //il faut récuperer le mdp en BDD et vérifier qu'ils sont identiques
        $salarie = Modele_Salarie::Salarie_Select_byId($_SESSION["idSalarie"]);
        if (password_verify($_REQUEST["AncienPassword"], $salarie["password"])) {
            //on vérifie si le mot de passe de la BDD est le même que celui rentré
            if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
                $this->vue->setEntete(new Vue_Structure_Entete());
                $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
                $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));
                Modele_Salarie::Salarie_Modifier_motDePasse($_SESSION["idSalarie"], $_REQUEST["NouveauPassword"]);
                $this->vue->addToCorps(new Vue_Compte_Administration_Gerer("<br><label><b>Votre mot de passe a bien été modifié</b></label>", "Gerer_MonCompte_Salarie"));
                // Dans ce cas les mots de passe sont bons, il est donc modifier

            } else {
                $this->vue->setEntete(new Vue_Structure_Entete());
                $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
                $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));

                $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<br><label><b>Les nouveaux mots de passe ne sont pas identiques</b></label>", "Gerer_MonCompte_Salarie"));
            }
        } else {
            $this->vue->setEntete(new Vue_Structure_Entete());
            $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
            $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));

            $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Vous n'avez pas saisi le bon mot de passe</b></label>", "Gerer_MonCompte_Salarie"));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function SeDeconnecter(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //L'utilisateur a cliqué sur "se déconnecter"
        session_destroy();
        unset($_SESSION);
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Cas par défaut: affichage du menu des actions.
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));
        $this->vue->addToCorps(new Vue_Compte_Administration_Gerer("", "Gerer_MonCompte_Salarie"));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
