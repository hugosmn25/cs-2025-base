<?php

namespace App\Controleur;

use App\Modele\Modele_Entreprise;
use App\Modele\Modele_Salarie;
use App\Modele\Modele_Utilisateur;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_ConsentementRGPD;
use App\Vue\Vue_Mail_Confirme;
use App\Vue\Vue_Mail_ReinitMdp;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Vue\Vue_Utilisateur_Changement_MDPForce;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function App\Fonctions\CalculComplexiteMdp;
use function App\Fonctions\envoyerMail;

class Controleur_visiteur
{
    private Vue $vue;

    private Controleur_Catalogue_client $catalogue_clientController;
    private Controleur_Gerer_Entreprise $entrepriseController;

    public function __construct(Vue $vue, Controleur_Catalogue_client $catalogue_client, Controleur_Gerer_Entreprise $entrepriseController)
    {
        $this->vue = $vue;
        $this->catalogue_clientController = $catalogue_client;
        $this->entrepriseController = $entrepriseController;
    }

    public function init(): void
    {
        // Entête et bas de page par défaut pour les visiteurs
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
    }

    public function reinitmdpconfirm(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!filter_var($_REQUEST["email"], FILTER_VALIDATE_EMAIL)) {
            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Vous devez saisir un mail valide</b></label>"));
        } else {


        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function reinitmdp(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function submitModifMDPForce(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
            $this->vue->setEntete(new Vue_Structure_Entete());

            Modele_Utilisateur::Utilisateur_Modifier_motDePasse($_SESSION["idUtilisateur"], $_REQUEST["NouveauPassword"]);
            Modele_Utilisateur::Utilisateur_Modifier_DoitChangerMdp($_SESSION["idUtilisateur"], (int) 0);
            $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);

            if ($utilisateur["aAccepteRGPD"] == 1) {
                $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                switch ($utilisateur["idCategorie_utilisateur"]) {
                    case 1:
                    case 2:
                        //$_SESSION["typeConnexionBack"] = "gestionnaireCatalogue";
                        $this->vue->setMenu(new Vue_Menu_Administration());
                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue !!"));
                        break;
                    case 3:
                        //$_SESSION["typeConnexionBack"] = "entrepriseCliente";
                        $_SESSION["idEntreprise"] = Modele_Entreprise::Entreprise_Select_Par_IdUtilisateur($_SESSION["idUtilisateur"])["idEntreprise"];
                        return $this->entrepriseController->default($request, $response, $args);

                    case 4:
                        //$_SESSION["typeConnexionBack"] = "salarieEntrepriseCliente";
                        $_SESSION["idSalarie"] = $utilisateur["idUtilisateur"];
                        $_SESSION["idEntreprise"] = Modele_Salarie::Salarie_Select_byId($_SESSION["idUtilisateur"])["idEntreprise"];
                        //  include "./Controleur/Controleur_Catalogue_client.php";
                        //$catalogueClientController = new \App\Controleur\Controleur_Catalogue_client($Vue);
                        return $this->catalogue_clientController->default($request, $response, $args);


                }
            } else {
                $this->vue->addToCorps(new Vue_ConsentementRGPD($utilisateur));
            }

        } else {
            $this->vue->setEntete(new Vue_Structure_Entete());
            $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDPForce("<label><b>Les nouveaux mots de passe ne sont pas identiques</b></label>"));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function SeConnecter(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_SESSION["idUtilisateur"])) {
            //l'utilisateur est encore connecté,
            //mais par un choix de navigation, il a demandé l'url de connexion. Donc on le déconnecte (on aurait pu le renvoyer sur sa page d'accueil)

            session_destroy();
            unset($_SESSION);
        }
        if (isset($_REQUEST["compte"]) and isset($_REQUEST["password"])) {
            $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParLogin($_REQUEST["compte"]);

            if ($utilisateur != null) {
                if ($utilisateur["desactiver"] == 0) {
                    if ($_REQUEST["password"] == $utilisateur["motDePasse"]) {
                        $_SESSION["idUtilisateur"] = $utilisateur["idUtilisateur"];
                        $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];

                        if ($utilisateur["DoitChangerMotDePasse"] == 1) {
                            $this->vue->addToCorps(new \App\Vue\Vue_Utilisateur_Changement_MDPForce());
                        } else {
                            if ($utilisateur["aAccepteRGPD"] == 1) {
                                $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                                switch ($utilisateur["idCategorie_utilisateur"]) {
                                    case 1:
                                    case 2:
                                        //$_SESSION["typeConnexionBack"] = "gestionnaireCatalogue";
                                        $this->vue->setMenu(new Vue_Menu_Administration());
                                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue !!"));
                                        break;
                                    case 3:
                                        //$_SESSION["typeConnexionBack"] = "entrepriseCliente";
                                        $_SESSION["idEntreprise"] = Modele_Entreprise::Entreprise_Select_Par_IdUtilisateur($_SESSION["idUtilisateur"])["idEntreprise"];
                                        return $this->entrepriseController->default($request, $response, $args);

                                    case 4:
                                        //$_SESSION["typeConnexionBack"] = "salarieEntrepriseCliente";
                                        $_SESSION["idSalarie"] = $utilisateur["idUtilisateur"];
                                        $_SESSION["idEntreprise"] = Modele_Salarie::Salarie_Select_byId($_SESSION["idUtilisateur"])["idEntreprise"];
                                        //  include "./Controleur/Controleur_Catalogue_client.php";
                                        //$catalogueClientController = new \App\Controleur\Controleur_Catalogue_client($Vue);
                                        return $this->catalogue_clientController->default($request, $response, $args);


                                }
                            } else {
                                $this->vue->addToCorps(new Vue_ConsentementRGPD($utilisateur));
                            }
                        }
                    } else {
                        $msgError = "Mot de passe erroné";
                        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
                    }
                } else {
                    $msgError = "Compte désactivé";
                    $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
                }
            } else {
                $msgError = "Identification invalide";
                $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
            }
        } else {
            $msgError = "Identification incomplete";
            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_SESSION["idUtilisateur"])) {
            //l'utilisateur est encore connecté,
            //mais par un choix de navigation, il a demandé l'url de connexion. Donc on le déconnecte (on aurait pu le renvoyer sur sa page d'accueil)

            session_destroy();
            unset($_SESSION);
        }
        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
