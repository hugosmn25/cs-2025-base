<?php

namespace App\Controleur;

use App\Modele\Modele_Entreprise;
use App\Modele\Modele_Utilisateur;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Entreprise_Formulaire;
use App\Vue\Vue_Entreprise_Liste;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use PHPMailer\PHPMailer\PHPMailer;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Gerer_entreprisesPartenaires
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
    }

    public function Modifer(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Modifier dans le formulaire de mise à jour
        $entreprise = Modele_Entreprise::Entreprise_Select_ParId($_REQUEST["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Entreprise_Formulaire(false, $entreprise["idEntreprise"], $entreprise["denomination"], $entreprise["rueAdresse"], $entreprise["complementAdresse"], $entreprise["codePostal"]
            , $entreprise["ville"], $entreprise["pays"], $entreprise["numCompte"], $entreprise["mailContact"], $entreprise["siret"]));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function mettreAJour(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Mettre à jour dans la liste des entreprises
        Modele_Entreprise::Entreprise_Modifier($_REQUEST["idEntreprise"], $_REQUEST["denomination"], $_REQUEST["rueAdresse"], $_REQUEST["complementAdresse"], $_REQUEST["codePostal"]
            , $_REQUEST["ville"], $_REQUEST["pays"], $_REQUEST["mailContact"], $_REQUEST["siret"]);
        $listeEntreprise = Modele_Entreprise::Entreprise_Select();
        $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
        $this->vue->addToCorps(new Vue_Entreprise_Liste($listeEntreprise));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function rÃ©initialiserMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Réinitialiser MDP sur la fiche de l'entreprise
        $entreprise = Modele_Entreprise::Entreprise_Select_ParId($_REQUEST["idEntreprise"]);

        $motDePasse = \App\Fonctions\GenereMDP(10);
        Modele_Entreprise::Entreprise_Modifier_motDePasse($_REQUEST["idEntreprise"], $motDePasse); //$entreprise["numCompte"]

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = '127.0.0.1';
        $mail->CharSet = "UTF-8";
        $mail->Port = 1025; //Port non crypté
        $mail->SMTPAuth = false; //Pas d’authentification
        $mail->SMTPAutoTLS = false; //Pas de certificat TLS
        $mail->setFrom('contact@labruleriecomtoise.fr', 'contact');
        $mail->addAddress($entreprise["mailContact"], $entreprise["denomination"]);
        if ($mail->addReplyTo('test@labruleriecomtoise.fr', 'admin')) {
            $mail->Subject = 'Objet : MDP !';
            $mail->isHTML(false);
            $mail->Body = "MDP $motDePasse";

            if (!$mail->send()) {
                $msg = 'Désolé, quelque chose a mal tourné. Veuillez réessayer plus tard.';
            } else {
                $msg = 'Message envoyé ! Merci de nous avoir contactés.';
            }
        } else {
            $msg = 'Il doit manquer qqc !';
        }
        //    echo $msg;
        $listeEntreprise = Modele_Entreprise::Entreprise_Select();
        $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
        $this->vue->addToCorps(new Vue_Entreprise_Liste($listeEntreprise));

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function nouveau(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Nouveau sur la liste des entreprises
        $this->vue->addToCorps(new Vue_Entreprise_Formulaire(true));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function buttonCreer(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Créer sur la fiche de création d'une entreprise
        Modele_Entreprise::Entreprise_Creer($_REQUEST["denomination"], $_REQUEST["rueAdresse"], $_REQUEST["complementAdresse"], $_REQUEST["codePostal"]
            , $_REQUEST["ville"], $_REQUEST["pays"], $_REQUEST["mailContact"], $_REQUEST["siret"]);
        $listeEntreprise = Modele_Entreprise::Entreprise_Select();

        $this->vue->addToCorps(new Vue_Entreprise_Liste($listeEntreprise));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function DesactiverEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Désactiver utilisateur ou réactiver utilisateur
        $Entreprise = Modele_Entreprise::Entreprise_Select_ParId($_REQUEST["idEntreprise"]);
        // champ desactiver valeur 0 : personne activée sur le site
        if ($Entreprise["desactiver"] == 0) {
            $Entreprise["desactiver"] = 1;
            Modele_Entreprise::Entreprise_Modifier_Desactivation($_REQUEST["idEntreprise"], $Entreprise["desactiver"]);

        } // champ desactiver valeur 1 : personne désactivée sur le site
        elseif ($Entreprise["desactiver"] == 1) {
            $Entreprise["desactiver"] = 0;
            Modele_Entreprise::Entreprise_Modifier_Desactivation($_REQUEST["idEntreprise"], $Entreprise["desactiver"]);
        }
        $listeEntreprise = Modele_Entreprise::Entreprise_Select();
       // $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
        $this->vue->addToCorps(new Vue_Entreprise_Liste($listeEntreprise));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ActiverEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Désactiver utilisateur ou réactiver utilisateur
        $Entreprise = Modele_Entreprise::Entreprise_Select_ParId($_REQUEST["idEntreprise"]);
        // champ desactiver valeur 0 : personne activée sur le site
        if ($Entreprise["desactiver"] == 0) {
            $Entreprise["desactiver"] = 1;
            Modele_Entreprise::Entreprise_Modifier_Desactivation($_REQUEST["idEntreprise"], $Entreprise["desactiver"]);

        } // champ desactiver valeur 1 : personne désactivée sur le site
        elseif ($Entreprise["desactiver"] == 1) {
            $Entreprise["desactiver"] = 0;
            Modele_Entreprise::Entreprise_Modifier_Desactivation($_REQUEST["idEntreprise"], $Entreprise["desactiver"]);
        }
        $listeEntreprise = Modele_Entreprise::Entreprise_Select();
        $this->vue->addToCorps(new Vue_Entreprise_Liste($listeEntreprise));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //situation par défaut :
        $listeEntreprise = Modele_Entreprise::Entreprise_Select();
        //$Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
        $this->vue->addToCorps(new Vue_Entreprise_Liste($listeEntreprise));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
