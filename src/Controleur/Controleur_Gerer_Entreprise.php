<?php

namespace App\Controleur;

use App\Modele\Modele_Entreprise;
use App\Modele\Modele_FacteurAuthentification;
use App\Modele\Modele_Salarie;
use App\Modele\Modele_Utilisateur;
use App\Vue\Vue_Compte_Administration_DeuxiemeFacteur;
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
use OTPHP\TOTP;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

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

    private function ajouterVueGestionCompte(string $message = ""): void
    {
        $this->vue->addToCorps(new Vue_Entreprise_Gerer_Compte($message));
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

        $this->vue->addToCorps(new Vue_Compte_Administration_DeuxiemeFacteur($facteurs, $facteurSelectionne, $message, "Gerer_Entreprise", "Configurer le deuxieme facteur"));
    }

    public function infoEntreprise(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->addToCorps(new Vue_Menu_Entreprise_Client());

        $entreprise = Modele_Entreprise::Entreprise_Select_ParId($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Entreprise_Information(
            $entreprise["idEntreprise"],
            $entreprise["denomination"],
            $entreprise["rueAdresse"],
            $entreprise["complementAdresse"],
            $entreprise["codePostal"]
            ,
            $entreprise["ville"],
            $entreprise["pays"],
            $entreprise["numCompte"],
            $entreprise["mailContact"],
            $entreprise["siret"]
        ));


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

    public function gerer2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        $this->ajouterVueDeuxiemeFacteur();
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function buttonCreerSalarie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());
        Modele_Salarie::Salarie_Ajouter(
            $_REQUEST["nom"],
            $_REQUEST["prenom"],
            $_REQUEST["role"],
            $_REQUEST["mailContact"],
            1,
            (int) $_SESSION["idEntreprise"]
        );
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

        Modele_Salarie::Salarie_MAJ($_REQUEST["nom"], $_REQUEST["prenom"], $_REQUEST["role"], $_REQUEST["mailContact"], $idSalarie);
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
                $this->ajouterVueGestionCompte("<label><b>Votre mot de passe a bien été modifié</b></label>");
                $this->vue->setBasDePage(new Vue_Structure_BasDePage());
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

    public function definir2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());

        $message = "<label><b>Erreur : sélection invalide.</b></label>";
        $idFacteur = filter_input(INPUT_POST, 'idFacteurAuthentification', FILTER_VALIDATE_INT);
        if ($idFacteur !== false && $idFacteur !== null) {
            $facteur = Modele_FacteurAuthentification::Facteur_SelectParId($idFacteur);
            if ($facteur === null) {
                $message = "<label><b>Erreur : facteur d'authentification introuvable.</b></label>";
            } else {
                $succes = Modele_FacteurAuthentification::Avoir2FA_DefinirPourUtilisateur((int) $_SESSION["idUtilisateur"], $idFacteur);
                switch ($facteur["nomFacteur"]) {
                    case "Mail":
                        // Envoyer un email de confirmation (simulation)
                        // Dans une vraie application, on enverrait un email ici
                        break;
                    case "OTP":
                        $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId((int) $_SESSION["idUtilisateur"]);
                            date_default_timezone_set('Europe/Paris');
                            $totp = TOTP::create(null,30,'sha1',6);                       // ou TOTP::create($secret) si tu as déjà un secret
                            $totp->setLabel( $utilisateur["login"]);
                            $totp->setIssuer("Cafe.local");
                            $uri = $totp->getProvisioningUri();

                            // --- Construit le QR (Endroid v6) ---
                            $builder = new Builder(
                                    writer: new PngWriter(),
                                    writerOptions: [],
                                    validateResult: false,
                                    data: $uri ,
                                    encoding: new Encoding('UTF-8'),
                                    errorCorrectionLevel: ErrorCorrectionLevel::High,
                                    size: 300,
                                    margin: 10,
                                    roundBlockSizeMode: RoundBlockSizeMode::Margin,
                                    logoPath: "",
                                    logoResizeToWidth: 50,
                                    logoPunchoutBackground: true,
                                    labelText: 'Qrcode OTP cafe.local',
                                    labelFont: new OpenSans(20),
                                    labelAlignment: LabelAlignment::Center
                                );

                                $result = $builder->build();        


                            // --- Affichage dans la page ---
                            $mime = $result->getMimeType();           // "image/png"
                            $base64 = base64_encode($result->getString());
                             
                            $message .= '<h2>Scanne ce QR Code :</h2>';
                            $message .= '<img src="data:image/png;base64,' . $base64 . '">';

                            Modele_FacteurAuthentification::Avoir2FA_MettreAJourValeur((int) $_SESSION["idUtilisateur"],  $totp->getSecret());

                            break;
                    // Ajouter d'autres cas pour d'autres types de facteurs si nécessaire
                }

                $message = $succes
                    ? "<label><b>Votre deuxième facteur d'authentification a été enregistré.</b></label>"
                    : "<label><b>Erreur lors de l'enregistrement du deuxième facteur.</b></label>";
            }
        }

        $this->ajouterVueDeuxiemeFacteur($message);
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function supprimer2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Entreprise_Client());

        $succes = Modele_FacteurAuthentification::Avoir2FA_SupprimerPourUtilisateur((int) $_SESSION["idUtilisateur"]);
        $message = $succes
            ? "<label><b>Votre deuxieme facteur d'authentification a ete supprime.</b></label>"
            : "<label><b>Erreur lors de la suppression du deuxieme facteur.</b></label>";

        $this->ajouterVueDeuxiemeFacteur($message);
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
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
        $this->ajouterVueGestionCompte();
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
