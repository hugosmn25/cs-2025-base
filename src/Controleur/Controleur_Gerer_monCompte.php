<?php

namespace App\Controleur;

use App\Modele\Modele_Utilisateur;
use App\Vue\Vue_Compte_Administration_Gerer;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Vue\Vue_Utilisateur_Changement_MDP;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function App\Fonctions\CalculComplexiteMdp;

class Controleur_Gerer_monCompte
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
        $this->vue->setMenu(new Vue_Menu_Administration());
        $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("", "Gerer_monCompte"));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function submitModifMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
         
        //il faut récuperer le mdp en BDD et vérifier qu'ils sont identiques
        $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
        if ($_REQUEST["AncienPassword"] == $utilisateur["motDePasse"])
        {
            //on vérifie si le mot de passe de la BDD est le même que celui rentré
            if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
                $this->vue->setEntete(new Vue_Structure_Entete());
                $this->vue->setMenu(new Vue_Menu_Administration());
                 
                    Modele_Utilisateur::Utilisateur_Modifier_motDePasse($_SESSION["idUtilisateur"], $_REQUEST["NouveauPassword"]);
                    $this->vue->addToCorps(new Vue_Compte_Administration_Gerer("<label><b>Votre mot de passe a bien été modifié</b></label>"));
                    // Dans ce cas les mots de passe sont bons, il est donc modifié
                
            } else {
                $this->vue->setEntete(new Vue_Structure_Entete());
                $this->vue->setMenu(new Vue_Menu_Administration());
                $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Les nouveaux mots de passe ne sont pas identiques</b></label>", "Gerer_monCompte"));
            }
        } else {
            $this->vue->setEntete(new Vue_Structure_Entete());
            $this->vue->setMenu(new Vue_Menu_Administration());
            $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Vous n'avez pas saisi le bon mot de passe</b></label>", "Gerer_monCompte"));
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
        $this->vue->setMenu(new Vue_Menu_Administration());
        $this->vue->addToCorps(new Vue_Compte_Administration_Gerer());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
