<?php

namespace App\Controleur;

use App\Modele\Modele_Entreprise;
use App\Modele\Modele_Salarie;
use App\Vue\Vue_Utilisateur_Changement_MDP;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Entreprise_Client;
use App\Vue\Vue_Entreprise_Gerer_Compte;
use App\Vue\Vue_Entreprise_Information;
use App\Vue\Vue_Salarie_Editer;
use App\Vue\Vue_Salarie_Liste;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function App\Fonctions\CalculComplexiteMdp;

class Controleur_Gerer_Entreprise
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

    public function infoEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->addToCorps(new Vue_Menu_Entreprise_Client());

        $entreprise = Modele_Entreprise::Entreprise_Select_ParId($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Entreprise_Information($entreprise["idEntreprise"], $entreprise["denomination"], $entreprise["rueAdresse"], $entreprise["complementAdresse"], $entreprise["codePostal"]
            , $entreprise["ville"], $entreprise["pays"], $entreprise["numCompte"], $entreprise["mailContact"], $entreprise["siret"]));


        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function salariesHabitites(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());

        $listeSalarie = Modele_Salarie::Salarie_Select_Entreprise($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Salarie_Liste($listeSalarie));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ajouterSalarie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $this->vue->addToCorps(new Vue_Salarie_Editer());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function buttonCreerSalarie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        Modele_Salarie::Salarie_Ajouter($_REQUEST["nom"], $_REQUEST["prenom"], $_REQUEST["role"], $_REQUEST["mailContact"],  1, 
        (int)$_SESSION["idEntreprise"]);
        $listeSalarie = Modele_Salarie::Salarie_Select_Entreprise($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Salarie_Liste($listeSalarie));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ModiferSalarie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idSalarie = $args['idSalarie'];
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $salarie = Modele_Salarie::Salarie_Select_byId($idSalarie);
        $this->vue->addToCorps(new Vue_Salarie_Editer(false, $idSalarie, $salarie["nom"], $salarie["prenom"], $salarie["roleEntreprise"], $salarie["mail"]));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ModiferSalarieValider(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idSalarie = $args['idSalarie'];
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());

        Modele_Salarie::Salarie_MAJ($_REQUEST["nom"], $_REQUEST["prenom"], $_REQUEST["role"], $_REQUEST["mailContact"], $idSalarie );
        $listeSalarie = Modele_Salarie::Salarie_Select_Entreprise($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Salarie_Liste($listeSalarie, "<br>Salarié modifié"));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function DesactiverSalarie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());

        Modele_Salarie::Salarie_Activer($_REQUEST["idSalarie"]);
        $listeSalarie = Modele_Salarie::Salarie_Select_Entreprise($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Salarie_Liste($listeSalarie, "<br>Salarié modifié"));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ActiverSalarie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());

        Modele_Salarie::Salarie_Desactiver($_REQUEST["idSalarie"]);
        $listeSalarie = Modele_Salarie::Salarie_Select_Entreprise($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Salarie_Liste($listeSalarie, "<br>Salarié modifié"));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function submitModifMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        //il faut récuperer le mdp en BDD et vérifier qu'ils sont identiques
        $entreprise_connectee = Modele_Entreprise::Entreprise_Select_ParId($_SESSION["idEntreprise"]);
        if (password_verify($_REQUEST["AncienPassword"], $entreprise_connectee["motDePasse"])) {
            //on vérifie si le mot de passe de la BDD est le même que celui rentré
            if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
                // Vérifie la complexité du nouveau mot de passe
                $bits = CalculComplexiteMdp($_REQUEST["NouveauPassword"]);
                if ($bits < 90) {
                    $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Complexité insuffisante (" . $bits . " bits). Minimum requis : 90 bits.</b></label>"));
                    $response->getBody()->write($this->vue->donneStr());
                    return $response;
                }
                Modele_Entreprise::Entreprise_Modifier_motDePasse($_SESSION["idEntreprise"], $_REQUEST["NouveauPassword"]);
                $this->vue->addToCorps(new Vue_Entreprise_Gerer_Compte("<label><b>Votre mot de passe a bien été modifié</b></label>"));
                // Dans ce cas les mots de passe sont bons, il est donc modifié

            } else {
                $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Les nouveaux mots de passe ne sont pas identiques</b></label>"));

            }
        } else {
            $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Vous n'avez pas saisi le bon mot de passe</b></label>"));

        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ChangerMDPEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function deconnexionEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        session_destroy();
        unset($_SESSION["idEntreprise"]);
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $this->vue->addToCorps(new Vue_Entreprise_Gerer_Compte());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
