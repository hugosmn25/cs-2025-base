<?php

namespace App\Controleur;

use App\Modele\Modele_FacteurAuthentification;
use App\Modele\Modele_Utilisateur;
use App\Vue\Vue_Compte_Administration_DeuxiemeFacteur;
use App\Vue\Vue_Compte_Administration_Gerer;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Vue\Vue_Utilisateur_Changement_MDP;
use App\Utilitaire\Vue;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function App\Fonctions\CalculComplexiteMdp;

use OTPHP\TOTP;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;


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

    private function ajouterVueGestionCompte(string $message = ""): void
    {
        $this->vue->addToCorps(new Vue_Compte_Administration_Gerer($message, "Gerer_monCompte"));
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

        $this->vue->addToCorps(new Vue_Compte_Administration_DeuxiemeFacteur($facteurs, $facteurSelectionne, $message, "Gerer_monCompte"));
    }

    public function changerMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
        $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("", "Gerer_monCompte"));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function gerer2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
        $this->ajouterVueDeuxiemeFacteur();
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function submitModifMDP(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
        if ($_REQUEST["AncienPassword"] == $utilisateur["motDePasse"]) {
            if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
                $this->vue->setEntete(new Vue_Structure_Entete());
                $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));

                $bits = CalculComplexiteMdp($_REQUEST["NouveauPassword"]);
                if ($bits < 90) {
                    $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Complexité insuffisante (" . $bits . " bits). Minimum requis : 90 bits.</b></label>", "Gerer_monCompte"));
                    $response->getBody()->write($this->vue->donneStr());
                    return $response;
                }

                Modele_Utilisateur::Utilisateur_Modifier_motDePasse($_SESSION["idUtilisateur"], $_REQUEST["NouveauPassword"]);
                $this->ajouterVueGestionCompte("<label><b>Votre mot de passe a bien été modifié</b></label>");
                $this->vue->setBasDePage(new Vue_Structure_BasDePage());
            } else {
                $this->vue->setEntete(new Vue_Structure_Entete());
                $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Les nouveaux mots de passe ne sont pas identiques</b></label>", "Gerer_monCompte"));
            }
        } else {
            $this->vue->setEntete(new Vue_Structure_Entete());
            $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
            $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDP("<label><b>Vous n'avez pas saisi le bon mot de passe</b></label>", "Gerer_monCompte"));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function definir2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));

        $message = "<label><b>Erreur : sélection invalide.</b></label>";
        $idFacteur = filter_input(INPUT_POST, 'idFacteurAuthentification', FILTER_VALIDATE_INT);
        if ($idFacteur !== false && $idFacteur !== null) {
            $facteur = Modele_FacteurAuthentification::Facteur_SelectParId($idFacteur);
            if ($facteur === null) {
                $message = "<label><b>Erreur : facteur d'authentification introuvable.</b></label>";
            } else {
                $succes = Modele_FacteurAuthentification::Avoir2FA_DefinirPourUtilisateur((int) $_SESSION["idUtilisateur"], $idFacteur);

                if ($succes) {
                    $message = "<label><b>Votre deuxième facteur d'authentification a été enregistré...</b></label>" ;

                    switch ($facteur["libelle"]) {
                        case "Mail":
                            /**  **/
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
                    }
                } else
                    $message = "<label><b>Erreur lors de l'enregistrement du deuxième facteur.</b></label>";
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
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));

        $succes = Modele_FacteurAuthentification::Avoir2FA_SupprimerPourUtilisateur((int) $_SESSION["idUtilisateur"]);
        $message = $succes
            ? "<label><b>Votre deuxieme facteur d'authentification a ete supprime.</b></label>"
            : "<label><b>Erreur lors de la suppression du deuxieme facteur.</b></label>";

        $this->ajouterVueDeuxiemeFacteur($message);
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
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
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
        $this->ajouterVueGestionCompte();
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
