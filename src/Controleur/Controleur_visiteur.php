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
use App\Vue\Vue_Utilisateur_Formulaire;
use App\Utilitaire\Vue;
use App\Modele\Modele_FinalitesConsentement;
use App\Modele\Modele_VersionsPolitique;
use App\Modele\Modele_Consentements;
use App\Modele\Modele_HistoriqueConnexion;
// (supprimé) use App\Modele\Modele_TentativesConnexion;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function App\Fonctions\CalculComplexiteMdp;
use function App\Fonctions\envoyerMail;
use function App\Fonctions\genereMDP;
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
            $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParLogin($_REQUEST["email"]);
            if ($Utilisateur != null) {
                $now = new \DateTimeImmutable('now');
                $motDePasseTemporaireActif = false;
                $expirationActuelle = null;
                $motDePasseTemporaireEnCours = $Utilisateur["motDePasseTemporaire"] ?? null;
                $expirationEnCours = $Utilisateur["expirationMotDePasseTemporaire"] ?? null;

                if (!empty($motDePasseTemporaireEnCours) && !empty($expirationEnCours)) {
                    try {
                        $expirationActuelle = new \DateTimeImmutable($expirationEnCours);
                    } catch (\Exception $e) {
                        $expirationActuelle = null;
                    }

                    if ($expirationActuelle && $expirationActuelle > $now) {
                        $motDePasseTemporaireActif = true;
                    } else {
                        Modele_Utilisateur::Utilisateur_SupprimerMotDePasseTemporaire($Utilisateur["idUtilisateur"]);
                        $Utilisateur["motDePasseTemporaire"] = null;
                        $Utilisateur["expirationMotDePasseTemporaire"] = null;
                        $motDePasseTemporaireEnCours = null;
                        $expirationActuelle = null;
                    }
                }

                if ($motDePasseTemporaireActif) {
                    $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                    $infoExpiration = $expirationActuelle ? $expirationActuelle->format('d/m/Y H:i') : "bientôt";
                    $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Un mot de passe temporaire est déjà actif. Vérifiez votre boîte mail ou réessayez après expiration (valide jusqu'au " . $infoExpiration . ").</b></label>"));
                } else {
                    $nouveauMdp = genereMDP(nbChar: 18);
                    $expiration = $now->add(new \DateInterval('PT1H'));

                    if (!Modele_Utilisateur::Utilisateur_DefinirMotDePasseTemporaire($Utilisateur["idUtilisateur"], $nouveauMdp, $expiration)) {
                        $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                        $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Impossible de préparer la réinitialisation du mot de passe.</b></label>"));
                    } else {
                        Modele_Utilisateur::Utilisateur_DoitChangerMdp($Utilisateur["idUtilisateur"], 1);
                        $expirationTexte = $expiration->format('d/m/Y H:i');
                        $messageMail = "Votre mot de passe temporaire est : <b>" . $nouveauMdp . "</b><br>Il expirera le " . $expirationTexte . ".<br>Utilisez-le pour vous connecter puis changez-le dès que possible.";
                        $resultat = envoyerMail("administration@cafe.local", "Administrateur café", $Utilisateur["login"], $Utilisateur["login"], "Réinitialisation de votre mot de passe", $messageMail);

                        switch ($resultat) {
                            case -1:
                                Modele_Utilisateur::Utilisateur_SupprimerMotDePasseTemporaire($Utilisateur["idUtilisateur"]);
                                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreurs de paramètres</b></label>"));
                                break;
                            case 0:
                                Modele_Utilisateur::Utilisateur_SupprimerMotDePasseTemporaire($Utilisateur["idUtilisateur"]);
                                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreur indéterminée</b></label>"));
                                break;
                            case 1:
                                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Mail envoyé. Le mot de passe temporaire expire le " . $expirationTexte . ".</b></label>"));
                                break;
                        }
                    }
                }
            } else {
                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Aucun utilisateur n'est enregistré avec ce mail</b></label>"));
            }

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
        // Session requise pour modifier le MDP imposé
        if (!isset($_SESSION["idUtilisateur"]) || empty($_SESSION["idUtilisateur"])) {
            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Session expirée. Veuillez vous reconnecter."));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }
        if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
            $this->vue->setEntete(new Vue_Structure_Entete());

            // Vérifie la complexité du nouveau mot de passe
            $bits = CalculComplexiteMdp($_REQUEST["NouveauPassword"]);
            if ($bits < 90) {
                $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDPForce("<label><b>Complexité insuffisante (" . $bits . " bits). Minimum requis : 90 bits.</b></label>"));
                $response->getBody()->write($this->vue->donneStr());
                return $response;
            }

            Modele_Utilisateur::Utilisateur_Modifier_motDePasse($_SESSION["idUtilisateur"], $_REQUEST["NouveauPassword"]);
            Modele_Utilisateur::Utilisateur_Modifier_DoitChangerMdp($_SESSION["idUtilisateur"], (int) 0);
            $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);

            if ($utilisateur["aAccepteRGPD"] == 1) {
                $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                switch ($utilisateur["idCategorie_utilisateur"]) {
                    case 1:
                    case 2:
                        //$_SESSION["typeConnexionBack"] = "gestionnaireCatalogue";
                        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue !!"));
                        break;
                    case 6:
                        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue dans l'espace RGPD"));
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
                $politique = Modele_VersionsPolitique::VersionsPolitique_Select_Courante();
                $finalites = Modele_FinalitesConsentement::FinalitesConsentement_Select_Actives();
                $consentsMap = [];
                foreach ($finalites as $f) {
                    $last = Modele_Consentements::Consentements_Select_Dernier_ByUtilisateur_Finalite($utilisateur["idUtilisateur"], (int) $f['id']);
                    if ($last) {
                        $consentsMap[(int) $f['id']] = $last['statut'];
                    }
                }
                $this->vue->addToCorps(new Vue_ConsentementRGPD($utilisateur, $politique, $finalites, $consentsMap));
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
            $loginSaisi = $_REQUEST["compte"];
            // Anti-bruteforce: 5 tentatives erronées -> blocage 2 minutes
            $nbEchecsRecents = Modele_HistoriqueConnexion::HistoriqueConnexion_NombreEchecsRecents($loginSaisi, 120);
            if ($nbEchecsRecents >= 5) {
                // Calcule une indication du temps restant si possible
                $dernier = Modele_HistoriqueConnexion::HistoriqueConnexion_DerniereEchec($loginSaisi);
                $msgError = "Trop de tentatives. Réessayez dans 2 minutes.";
                if ($dernier) {
                    $resteSec = 120;
                    try {
                        $tsDernier = strtotime($dernier);
                        if ($tsDernier) {
                            $resteSec = max(0, 120 - (time() - $tsDernier));
                        }
                    } catch (\Throwable $e) {
                    }
                    if ($resteSec > 0) {
                        $min = intdiv($resteSec, 60);
                        $sec = $resteSec % 60;
                        $msgError = sprintf("Trop de tentatives. Réessayez dans %d:%02d.", $min, $sec);
                    }
                }
                $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
                $response->getBody()->write($this->vue->donneStr());
                return $response;
            }

            $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParLogin($loginSaisi);

            if ($utilisateur != null) {
                if ($utilisateur["desactiver"] == 0) {
                    $motDePasseSaisi = $_REQUEST["password"];
                    $motDePasseValide = false;
                    $motDePasseTemporaireValide = false;
                    $motDePasseTemporaireExpireSaisi = false;

                    $motDePasseTemporaireInitial = $utilisateur["motDePasseTemporaire"] ?? null;
                    $motDePasseTemporaireEnCours = $motDePasseTemporaireInitial;
                    $expirationTemporaireEnCours = $utilisateur["expirationMotDePasseTemporaire"] ?? null;
                    $dateExpirationTemporaire = null;
                    if (!empty($motDePasseTemporaireEnCours) && !empty($expirationTemporaireEnCours)) {
                        try {
                            $dateExpirationTemporaire = new \DateTimeImmutable($expirationTemporaireEnCours);
                        } catch (\Exception $e) {
                            $dateExpirationTemporaire = null;
                        }
                        $now = new \DateTimeImmutable('now');
                        if ($dateExpirationTemporaire && $dateExpirationTemporaire > $now) {
                            $motDePasseTemporaireValide = true;
                        } else {
                            Modele_Utilisateur::Utilisateur_SupprimerMotDePasseTemporaire($utilisateur["idUtilisateur"]);
                            $motDePasseTemporaireEnCours = null;
                        }
                    }

                    if ($motDePasseSaisi === $utilisateur["motDePasse"]) {
                        $motDePasseValide = true;
                    } elseif ($motDePasseTemporaireValide && $motDePasseSaisi === $motDePasseTemporaireEnCours) {
                        $motDePasseValide = true;
                        Modele_Utilisateur::Utilisateur_Modifier_motDePasse($utilisateur["idUtilisateur"], $motDePasseTemporaireEnCours);
                        Modele_Utilisateur::Utilisateur_DoitChangerMdp($utilisateur["idUtilisateur"], 1);
                        $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($utilisateur["idUtilisateur"]);
                    } elseif (!$motDePasseTemporaireValide && !empty($motDePasseTemporaireInitial) && $motDePasseSaisi === $motDePasseTemporaireInitial) {
                        $motDePasseTemporaireExpireSaisi = true;
                    }

                    if ($motDePasseValide) {
                        $_SESSION["idUtilisateur"] = $utilisateur["idUtilisateur"];
                        $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                        // Enregistre succès
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

                        // Dernière connexion (avant celle-ci)
                        $derniereConnexion = Modele_HistoriqueConnexion::HistoriqueConnexion_Derniere($utilisateur["idUtilisateur"]);
                        // Enregistre la connexion courante
                        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        Modele_HistoriqueConnexion::HistoriqueConnexion_Ajouter($utilisateur["idUtilisateur"], $ip, $ua);

                        if ($utilisateur["DoitChangerMotDePasse"] == 1) {
                            $this->vue->addToCorps(new \App\Vue\Vue_Utilisateur_Changement_MDPForce());
                        } else {
                            if ($utilisateur["aAccepteRGPD"] == 1) {
                                $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                                switch ($utilisateur["idCategorie_utilisateur"]) {
                                    case 1:
                                    case 2:
                                        //$_SESSION["typeConnexionBack"] = "gestionnaireCatalogue";
                                        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue !!"));
                                        if ($derniereConnexion) {
                                            $this->vue->addToCorps(new Vue_AfficherMessage("Dernière connexion : " . $derniereConnexion));
                                        }
                                        break;
                                    case 6:
                                        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue dans l'espace RGPD"));
                                        if ($derniereConnexion) {
                                            $this->vue->addToCorps(new Vue_AfficherMessage("Dernière connexion : " . $derniereConnexion));
                                        }
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
                                $politique = Modele_VersionsPolitique::VersionsPolitique_Select_Courante();
                                $finalites = Modele_FinalitesConsentement::FinalitesConsentement_Select_Actives();
                                $consentsMap = [];
                                foreach ($finalites as $f) {
                                    $last = Modele_Consentements::Consentements_Select_Dernier_ByUtilisateur_Finalite($utilisateur["idUtilisateur"], (int) $f['id']);
                                    if ($last) {
                                        $consentsMap[(int) $f['id']] = $last['statut'];
                                    }
                                }
                                $this->vue->addToCorps(new Vue_ConsentementRGPD($utilisateur, $politique, $finalites, $consentsMap));
                            }
                        }
                    } else {
                        // Enregistre échec
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        Modele_HistoriqueConnexion::HistoriqueConnexion_EnregistrerTentative($loginSaisi, false, $utilisateur["idUtilisateur"], $ip, $ua);
                        $nbEchecs = Modele_HistoriqueConnexion::HistoriqueConnexion_NombreEchecsRecents($loginSaisi, 120);
                        if ($motDePasseTemporaireExpireSaisi) {
                            $msgError = "Mot de passe temporaire expire. Veuillez redemander une reinitialisation.";
                        } else {
                            $restantes = max(0, 5 - $nbEchecs);
                            $msgError = $restantes > 0
                                ? "Mot de passe erroné. Tentatives restantes avant blocage: " . $restantes
                                : "Mot de passe erroné. Compte temporairement bloqué (2 minutes).";
                        }
                        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
                    }
                } else {
                    $msgError = "Compte désactivé";
                    $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
                }
            } else {
                // Enregistre échec sur un login inexistant également
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                Modele_HistoriqueConnexion::HistoriqueConnexion_EnregistrerTentative($loginSaisi, false, null, $ip, $ua);
                $nbEchecs = Modele_HistoriqueConnexion::HistoriqueConnexion_NombreEchecsRecents($loginSaisi, 120);
                $restantes = max(0, 5 - $nbEchecs);
                $msgError = $restantes > 0
                    ? "Identification invalide. Tentatives restantes avant blocage: " . $restantes
                    : "Identification invalide. Compte temporairement bloqué (2 minutes).";
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
