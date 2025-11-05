<?php

namespace App\Controleur;

use App\Modele\Modele_Commande;
use App\Modele\Modele_FacteurAuthentification;
use App\Modele\Modele_Salarie;
use App\Vue\Vue_Compte_Administration_DeuxiemeFacteur;
use App\Vue\Vue_Compte_Administration_Gerer;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Entreprise_Salarie;
use App\Vue\Vue_Utilisateur_Changement_MDP;
use App\Utilitaire\Vue;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function App\Fonctions\CalculComplexiteMdp;

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

    private function ajouterVueGestionCompte(string $message = ""): void
    {
        $this->vue->addToCorps(new Vue_Compte_Administration_Gerer($message, "Gerer_MonCompte_Salarie"));
    }

    private function ajouterVueDeuxiemeFacteur(string $message = ""): void
    {
        $facteurs = Modele_FacteurAuthentification::Facteur_SelectTout();
        $facteurSelectionne = null;
        if (isset($_SESSION["idUtilisateur"])) {
            $selection = Modele_FacteurAuthentification::Avoir2FA_SelectParUtilisateur((int) $_SESSION["idUtilisateur"]);
            if ($selection !== null && isset($selection["idFacteurAuthentification"])) {
                $facteurSelectionne = (int) $selection["idFacteurAuthentification"];
            }
        }

        $this->vue->addToCorps(new Vue_Compte_Administration_DeuxiemeFacteur($facteurs, $facteurSelectionne, $message, "Gerer_MonCompte_Salarie"));
    }

    public function gerer2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));
        $this->ajouterVueDeuxiemeFacteur();
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function changerMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
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
        $salarie = Modele_Salarie::Salarie_Select_byId($_SESSION["idSalarie"]);
        if (password_verify($_REQUEST["AncienPassword"], $salarie["password"])) {
            if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
                $this->vue->setEntete(new Vue_Structure_Entete());
                $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
                $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));

                $bits = CalculComplexiteMdp($_REQUEST["NouveauPassword"]);
                if ($bits < 90) {
                    $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<br><label><b>Complexité insuffisante (" . $bits . " bits). Minimum requis : 90 bits.</b></label>", "Gerer_MonCompte_Salarie"));
                    $response->getBody()->write($this->vue->donneStr());
                    return $response;
                }

                Modele_Salarie::Salarie_Modifier_motDePasse($_SESSION["idSalarie"], $_REQUEST["NouveauPassword"]);
                $this->ajouterVueGestionCompte("<br><label><b>Votre mot de passe a bien été modifié</b></label>");
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

    public function definir2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));

        $message = "<br><label><b>Erreur : sélection invalide.</b></label>";
        $idFacteur = filter_input(INPUT_POST, 'idFacteurAuthentification', FILTER_VALIDATE_INT);
        if ($idFacteur !== false && $idFacteur !== null) {
            $facteur = Modele_FacteurAuthentification::Facteur_SelectParId($idFacteur);
            if ($facteur === null) {
                $message = "<br><label><b>Erreur : facteur d'authentification introuvable.</b></label>";
            } else {
                $succes = Modele_FacteurAuthentification::Avoir2FA_DefinirPourUtilisateur((int) $_SESSION["idUtilisateur"], $idFacteur);
                $message = $succes
                    ? "<br><label><b>Votre deuxième facteur d'authentification a été enregistré.</b></label>"
                    : "<br><label><b>Erreur lors de l'enregistrement du deuxième facteur.</b></label>";
            }
        }

        $this->ajouterVueDeuxiemeFacteur($message);
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function supprimer2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));

        $succes = Modele_FacteurAuthentification::Avoir2FA_SupprimerPourUtilisateur((int) $_SESSION["idUtilisateur"]);
        $message = $succes
            ? "<br><label><b>Votre deuxieme facteur d'authentification a ete supprime.</b></label>"
            : "<br><label><b>Erreur lors de la suppression du deuxieme facteur.</b></label>";

        $this->ajouterVueDeuxiemeFacteur($message);
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function SeDeconnecter(Request $request, Response $response, array $args): Response
    {
        $this->init();
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
        $this->vue->setEntete(new Vue_Structure_Entete());
        $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);
        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));
        $this->ajouterVueGestionCompte();
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
