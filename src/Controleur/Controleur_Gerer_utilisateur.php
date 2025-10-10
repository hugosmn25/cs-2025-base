<?php

namespace App\Controleur;

use App\Modele\Modele_categorie_utilisateur;
use App\Modele\Modele_Utilisateur;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Vue\Vue_Utilisateur_Formulaire;
use App\Vue\Vue_Utilisateur_Liste;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function App\Fonctions\envoyerMail;
use function App\Fonctions\GenereMDP;

class Controleur_Gerer_utilisateur
{
    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    public function init(): void
    {
         
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
    }

    public function ModifierUtilisateur(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Modifier dans le formulaire de mise à jour
        $idUtilisateur = (int)($args['id'] ?? 0);
        $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateur_Select();
        $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($idUtilisateur);
        $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(false, $listeNiveauAutorisation, $idUtilisateur, $Utilisateur["login"], $Utilisateur["idCategorie_utilisateur"]));

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function mettreAJourUtilisateur(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idUtilisateur = (int)($args['id'] ?? 0);
        // Validation: le login doit être un email valide
        if (!filter_var($_REQUEST["login"], FILTER_VALIDATE_EMAIL)) {
            $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateur_Select();
            $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(false, $listeNiveauAutorisation, (string)$idUtilisateur, $_REQUEST["login"], $_REQUEST["codeCategorie"]));
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Vous devez saisir un mail valide</b></label>"));

            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        //Mettre à jour dans la liste des utilisateurs
        Modele_Utilisateur::Utilisateur_Modifier($idUtilisateur, $_REQUEST["login"], $_REQUEST["codeCategorie"]);
        $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($idUtilisateur);

        $listeUtilisateur = Modele_Utilisateur:: Utilisateur_Select_Cafe();
        $this->vue->addToCorps(new Vue_Utilisateur_Liste($listeUtilisateur));

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function reinitialiserMDPUtilisateur(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idUtilisateur = (int)($args['id'] ?? 0);
        //Réinitialiser MDP sur la fiche de l'entreprise
        $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($idUtilisateur);
        if(!filter_var($Utilisateur["login"], FILTER_VALIDATE_EMAIL)){
            $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateur_Select();
             $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(false, $listeNiveauAutorisation, $Utilisateur["idUtilisateur"], $Utilisateur["login"], $Utilisateur["idCategorie_utilisateur"]));
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Vous ne pouvez pas réinitialiser le mot de passe tant que le login n'a pas la forme d'un mail</b></label>"));

        }
        else {
            $nouveauMdp = genereMDP(12);
            $resultat=envoyerMail("administration@cafe.local", "Administrateur café", $Utilisateur["login"],$Utilisateur["login"],  "Réinitialisation de votre mot de passe", "Votre nouveau mot de passe est : " . $nouveauMdp);

            switch ($resultat)
            {
                case -1 :
                    $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateur_Select();
                    $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(false, $listeNiveauAutorisation, $Utilisateur["idUtilisateur"], $Utilisateur["login"], $Utilisateur["idCategorie_utilisateur"]));
                    $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreurs de paramètres</b></label>"));
                    break;
                case 0 :
                    $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateur_Select();
                    $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(false, $listeNiveauAutorisation, $Utilisateur["idUtilisateur"], $Utilisateur["login"], $Utilisateur["idCategorie_utilisateur"]));
                    $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreur indéterminée</b></label>"));
                    break;
                case 1 :
                    Modele_Utilisateur::Utilisateur_Modifier_motDePasse($idUtilisateur, $nouveauMdp); //$Utilisateur["idUtilisateur"]
                    Modele_Utilisateur::Utilisateur_DoitChangerMdp($idUtilisateur, 1);
                    $listeUtilisateur = Modele_Utilisateur:: Utilisateur_Select_Cafe();
                    $this->vue->addToCorps(new Vue_Utilisateur_Liste($listeUtilisateur));
                    $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Mail envoyé</b></label>"));
                    break;
            }
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function nouveau(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //Nouveau sur la liste des utilisateurs
        $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateurBack_Select();
        $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(true, $listeNiveauAutorisation));

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function buttonCreerUtilisateur(Request $request, Response $response, array $args): Response
    {
        $this->init();
        // On regarde si le login est disponible : il ne faut pas que deux personnes aient le même login !
        $login_nouveau = $_REQUEST["login"];

        // Validation: le login doit être un email valide
        if (!filter_var($login_nouveau, FILTER_VALIDATE_EMAIL)) {
            $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateurBack_Select();
            $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(true, $listeNiveauAutorisation));
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Vous devez saisir un mail valide</b></label>"));

            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }
        $listeUtilisateur = Modele_Utilisateur:: Utilisateur_Select_Cafe();
        $login_deja_attribue = false;
        for ($i = 0; $i < count($listeUtilisateur); $i++) {
            $iemeUtilisateur = $listeUtilisateur[$i];
            if ($login_nouveau == $iemeUtilisateur["login"]) {
                $login_deja_attribue = true;
            }
        }
        if ($login_deja_attribue == true) {
            $listeNiveauAutorisation = Modele_categorie_utilisateur::categorie_utilisateur_Select();
            $this->vue->addToCorps(new Vue_Utilisateur_Formulaire(true, $listeNiveauAutorisation));
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Ce login est déjà attribué, veuillez saisir un autre login</b></label>"));
        } else {
            //Créer sur la fiche de création d'une utilisateurs
            Modele_Utilisateur::Utilisateur_Creer($_REQUEST["login"], "secret", $_REQUEST["codeCategorie"]);
            //Redirect_Self_URL();
            $listeUtilisateur = Modele_Utilisateur:: Utilisateur_Select_Cafe();
            $this->vue->addToCorps(new Vue_Utilisateur_Liste($listeUtilisateur, "Utilisateur créé"));
        }

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function DesactiverUtilisateur(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idUtilisateur = (int)($args['id'] ?? 0);
        //Désactiver utilisateur ou réactiver utilisateur
        $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($idUtilisateur);
        // champ desactiver valeur 0 : personne activée sur le site

        $Utilisateur["desactiver"] = 1;
        Modele_Utilisateur::Utilisateur_Modifier_Desactivation($idUtilisateur, $Utilisateur["desactiver"]);

        $listeUtilisateur = Modele_Utilisateur:: Utilisateur_Select_Cafe();
        $this->vue->addToCorps(new Vue_Utilisateur_Liste($listeUtilisateur));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ActiverUtilisateur(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idUtilisateur = (int)($args['id'] ?? 0);
        //Désactiver utilisateur ou réactiver utilisateur
        $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($idUtilisateur);

        $Utilisateur["desactiver"] = 0;
        Modele_Utilisateur::Utilisateur_Modifier_Desactivation($idUtilisateur, $Utilisateur["desactiver"]);

        $listeUtilisateur = Modele_Utilisateur:: Utilisateur_Select_Cafe();
        $this->vue->addToCorps(new Vue_Utilisateur_Liste($listeUtilisateur));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        //situation par défaut :
        $listeUtilisateur = Modele_Utilisateur:: Utilisateur_Select_Cafe();
        $this->vue->addToCorps(new Vue_Utilisateur_Liste($listeUtilisateur));
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}

