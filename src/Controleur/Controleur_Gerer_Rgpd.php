<?php
namespace App\Controleur;

use App\Modele\Modele_Entreprise;
use App\Modele\Modele_Salarie;
use App\Modele\Modele_Utilisateur;
use App\Utilitaire\Vue;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_ConsentementRGPD;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_Entete;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Gerer_Rgpd
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
        $this->vue->setEntete(new Vue_Structure_Entete());
        //$this->vue->addToCorps(new Vue_AfficherMessage("<br>Controleur rgpd<br>"));
    }

    public function validerRGPD(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (isset($_REQUEST["accepterRGPD"])) {
            if ($_REQUEST["accepterRGPD"] == 0) {
                session_destroy();
                unset($_SESSION);
                $this->vue->setEntete(new Vue_Structure_Entete());
                $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
            } else {


                $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
                if ($utilisateur != null) {
                    Modele_Utilisateur::Utilisateur_UpdateRgpd($utilisateur["idUtilisateur"], $_REQUEST["accepterRGPD"], $_SERVER['REMOTE_ADDR']);
                    $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                    switch ($utilisateur["idCategorie_utilisateur"]) {
                        case 1:
                        case 2:
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
                    $this->vue->addToCorps(new \App\Vue\Vue_AfficherMessage("Erreur utilisateur non trouvé"));

                }
            }
        } else {
            $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);

            $this->vue->addToCorps(new Vue_ConsentementRGPD($utilisateur));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

}
