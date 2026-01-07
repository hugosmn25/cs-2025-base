<?php

namespace App\Controleur;

use App\Modele\Modele_Entreprise;
use App\Modele\Modele_Salarie;
use App\Modele\Modele_FacteurAuthentification;
use App\Modele\Modele_Utilisateur;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_ConsentementRGPD;
use App\Vue\Vue_Mail_ReinitMdp;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Vue\Vue_Utilisateur_Changement_MDPForce;
use App\Vue\Vue_Connexion_Second_Facteur;
use App\Utilitaire\Vue;
use App\Modele\Modele_FinalitesConsentement;
use App\Modele\Modele_VersionsPolitique;
use App\Modele\Modele_Consentements;
use App\Modele\Modele_HistoriqueConnexion;
use App\Modele\Modele_Token;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function App\Fonctions\CalculComplexiteMdp;
use function App\Fonctions\envoyerMail;
use function App\Fonctions\genereMDP;
use OTPHP\TOTP;

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

    /**
     * Fonction finalisant la connexion d'un utilisateur après authentification réussie
     * @param array<string, mixed> $utilisateur
     */
    private function finaliserConnexion(array $utilisateur, Request $request, Response $response, array $args): Response
    {
        $_SESSION["idUtilisateur"] = (int) $utilisateur["idUtilisateur"];
        $_SESSION["idCategorie_utilisateur"] = (int) $utilisateur["idCategorie_utilisateur"];

        // LOG: connexion finalisée
        $GLOBALS['logger']->info('auth.session.established', log_ctx([
            'user_id' => (int) $utilisateur["idUtilisateur"],
            'role'    => (int) $utilisateur["idCategorie_utilisateur"],
        ]));

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $derniereConnexion = Modele_HistoriqueConnexion::HistoriqueConnexion_Derniere((int) $utilisateur["idUtilisateur"]);
        Modele_HistoriqueConnexion::HistoriqueConnexion_Ajouter((int) $utilisateur["idUtilisateur"], $ip, $ua);

        if ((int) $utilisateur["DoitChangerMotDePasse"] === 1) {
            // LOG: mot de passe imposé
            $GLOBALS['logger']->info('auth.password_change.required', log_ctx([
                'user_id' => (int) $utilisateur["idUtilisateur"],
            ]));

            $this->vue->addToCorps(new \App\Vue\Vue_Utilisateur_Changement_MDPForce());
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        if ((int) $utilisateur["aAccepteRGPD"] === 1) {
            switch ((int) $utilisateur["idCategorie_utilisateur"]) {
                case 1:
                case 2:
                    $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                    $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue !!"));
                    if (!empty($derniereConnexion)) {
                        $this->vue->addToCorps(new Vue_AfficherMessage("Derniere connexion : " . $derniereConnexion));
                    }
                    break;
                case 6:
                    $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                    $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue dans l'espace RGPD"));
                    if (!empty($derniereConnexion)) {
                        $this->vue->addToCorps(new Vue_AfficherMessage("Derniere connexion : " . $derniereConnexion));
                    }
                    break;
                case 3:
                    $_SESSION["idEntreprise"] = Modele_Entreprise::Entreprise_Select_Par_IdUtilisateur($_SESSION["idUtilisateur"])["idEntreprise"];
                    return $this->entrepriseController->default($request, $response, $args);
                case 4:
                    $_SESSION["idSalarie"] = (int) $utilisateur["idUtilisateur"];
                    $_SESSION["idEntreprise"] = Modele_Salarie::Salarie_Select_byId($_SESSION["idUtilisateur"])["idEntreprise"];
                    return $this->catalogue_clientController->default($request, $response, $args);
                default:
                    break;
            }
        } else {
            // LOG: consentement RGPD requis
            $GLOBALS['logger']->info('rgpd.consent.required', log_ctx([
                'user_id' => (int) $utilisateur["idUtilisateur"],
            ]));

            $politique = Modele_VersionsPolitique::VersionsPolitique_Select_Courante();
            $finalites = Modele_FinalitesConsentement::FinalitesConsentement_Select_Actives();
            $consentsMap = [];
            foreach ($finalites as $f) {
                $last = Modele_Consentements::Consentements_Select_Dernier_ByUtilisateur_Finalite((int) $utilisateur["idUtilisateur"], (int) $f['id']);
                if ($last) {
                    $consentsMap[(int) $f['id']] = $last['statut'];
                }
            }
            $this->vue->addToCorps(new Vue_ConsentementRGPD($utilisateur, $politique, $finalites, $consentsMap));
        }

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function reinitmdpconfirm(Request $request, Response $response, array $args): Response
    {
        $this->init();

        $email = (string) ($_REQUEST["email"] ?? '');

        // LOG: demande reset (même si email invalide)
        $GLOBALS['logger']->info('auth.password_reset.request', log_ctx([
            'email' => $email,
            'mode'  => 'temporary_password',
        ]));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $GLOBALS['logger']->warning('auth.password_reset.invalid_email', log_ctx([
                'email' => $email
            ]));

            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Vous devez saisir un mail valide</b></label>"));
        } else {
            $Utilisateur = Modele_Utilisateur::Utilisateur_Select_ParLogin($email);
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
                    $GLOBALS['logger']->info('auth.password_reset.already_active', log_ctx([
                        'user_id' => (int) $Utilisateur["idUtilisateur"],
                        'email'   => $email,
                        'mode'    => 'temporary_password',
                    ]));

                    $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                    $infoExpiration = $expirationActuelle ? $expirationActuelle->format('d/m/Y H:i') : "bientôt";
                    $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Un mot de passe temporaire est déjà actif. Vérifiez votre boîte mail ou réessayez après expiration (valide jusqu'au " . $infoExpiration . ").</b></label>"));
                } else {
                    $nouveauMdp = genereMDP(nbChar: 18);
                    $expiration = $now->add(new \DateInterval('PT1H'));

                    if (!Modele_Utilisateur::Utilisateur_DefinirMotDePasseTemporaire($Utilisateur["idUtilisateur"], $nouveauMdp, $expiration)) {
                        $GLOBALS['logger']->error('auth.password_reset.prepare_failed', log_ctx([
                            'user_id' => (int) $Utilisateur["idUtilisateur"],
                            'email'   => $email,
                            'mode'    => 'temporary_password',
                        ]));

                        $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                        $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Impossible de préparer la réinitialisation du mot de passe.</b></label>"));
                    } else {
                        $expirationTexte = $expiration->format('d/m/Y H:i');
                        $messageMail = "Votre mot de passe temporaire est : <b>" . $nouveauMdp . "</b><br>Il expirera le " . $expirationTexte . ".<br>Utilisez-le pour vous connecter puis changez-le dès que possible.";
                        $resultat = envoyerMail("administration@cafe.local", "Administrateur café", $Utilisateur["login"], $Utilisateur["login"], "Réinitialisation de votre mot de passe", $messageMail);

                        switch ($resultat) {
                            case -1:
                                Modele_Utilisateur::Utilisateur_SupprimerMotDePasseTemporaire($Utilisateur["idUtilisateur"]);
                                $GLOBALS['logger']->warning('auth.password_reset.send_failed', log_ctx([
                                    'user_id' => (int) $Utilisateur["idUtilisateur"],
                                    'email'   => $email,
                                    'mode'    => 'temporary_password',
                                    'reason'  => 'mail_params',
                                ]));
                                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreurs de paramètres</b></label>"));
                                break;
                            case 0:
                                Modele_Utilisateur::Utilisateur_SupprimerMotDePasseTemporaire($Utilisateur["idUtilisateur"]);
                                $GLOBALS['logger']->warning('auth.password_reset.send_failed', log_ctx([
                                    'user_id' => (int) $Utilisateur["idUtilisateur"],
                                    'email'   => $email,
                                    'mode'    => 'temporary_password',
                                    'reason'  => 'mail_unknown',
                                ]));
                                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreur indéterminée</b></label>"));
                                break;
                            case 1:
                                $GLOBALS['logger']->info('auth.password_reset.sent', log_ctx([
                                    'user_id' => (int) $Utilisateur["idUtilisateur"],
                                    'email'   => $email,
                                    'mode'    => 'temporary_password',
                                    'expires' => $expiration->format(DATE_ATOM),
                                ]));
                                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Mail envoyé. Le mot de passe temporaire expire le " . $expirationTexte . ".</b></label>"));
                                break;
                        }
                    }
                }
            } else {
                $GLOBALS['logger']->warning('auth.password_reset.user_not_found', log_ctx([
                    'email' => $email,
                    'mode'  => 'temporary_password',
                ]));

                $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
                $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Aucun utilisateur n'est enregistré avec ce mail</b></label>"));
            }
        }

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function reinitmdpconfirmtoken(Request $request, Response $response, array $args): Response
    {
        $this->init();

        $email = (string) ($_REQUEST["email"] ?? '');

        $GLOBALS['logger']->info('auth.password_reset.request', log_ctx([
            'email' => $email,
            'mode'  => 'token_link',
        ]));

        if (!isset($_REQUEST["email"]) || !filter_var($_REQUEST["email"], FILTER_VALIDATE_EMAIL)) {
            $GLOBALS['logger']->warning('auth.password_reset.invalid_email', log_ctx([
                'email' => $email,
                'mode'  => 'token_link',
            ]));

            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Vous devez saisir un mail valide</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParLogin($_REQUEST["email"]);
        if ($utilisateur === null) {
            $GLOBALS['logger']->warning('auth.password_reset.user_not_found', log_ctx([
                'email' => $email,
                'mode'  => 'token_link',
            ]));

            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Aucun utilisateur n'est enregistré avec ce mail</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $tokenActif = Modele_Token::Token_SelectActifParUtilisateur((int) $utilisateur["idUtilisateur"], 1);
        if ($tokenActif !== null) {
            $GLOBALS['logger']->info('auth.password_reset.already_active', log_ctx([
                'user_id' => (int) $utilisateur["idUtilisateur"],
                'email'   => $email,
                'mode'    => 'token_link',
            ]));

            try {
                $expirationActive = new \DateTimeImmutable($tokenActif["dateFin"]);
                $infoExpiration = $expirationActive->format('d/m/Y H:i');
            } catch (\Exception $e) {
                $infoExpiration = "bientôt";
            }
            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Un lien de réinitialisation a déjà été envoyé. Vérifiez vos e-mails (valide jusqu'au " . $infoExpiration . ").</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $now = new \DateTimeImmutable('now');
        $expiration = $now->add(new \DateInterval('PT1H'));
        // Supprime les anciens tokens éventuels pour ce compte
        Modele_Token::Token_SupprimerParUtilisateur((int) $utilisateur["idUtilisateur"], 1);

        $tokenValeur = Modele_Token::CreerToken(1, (int) $utilisateur["idUtilisateur"], $expiration);
        if ($tokenValeur === false) {
            $GLOBALS['logger']->error('auth.password_reset.token_create_failed', log_ctx([
                'user_id' => (int) $utilisateur["idUtilisateur"],
                'email'   => $email,
                'mode'    => 'token_link',
            ]));

            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Erreur : Impossible de générer un lien de réinitialisation.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
            $basePath = '';
        }
        $url = $scheme . '://' . $host . $basePath . '/reinitmdp/token/?token=' . rawurlencode($tokenValeur);

        $expirationTexte = $expiration->format('d/m/Y H:i');
        $messageLien = "Vous avez demandé la réinitialisation de votre mot de passe.<br>"
            . "Cliquez sur le lien suivant (valide jusqu'au " . $expirationTexte . ") : "
            . "<a href='" . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "'>Réinitialiser mon mot de passe</a><br>"
            . "Si le lien ne fonctionne pas, copiez-collez l'URL suivante dans votre navigateur :<br>" . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $resultat = envoyerMail("administration@cafe.local", "Administrateur café", $utilisateur["login"], $utilisateur["login"], "Lien de réinitialisation de mot de passe", $messageLien);

        if ($resultat !== 1) {
            Modele_Token::Token_SupprimerParValeur($tokenValeur);

            $GLOBALS['logger']->warning('auth.password_reset.send_failed', log_ctx([
                'user_id' => (int) $utilisateur["idUtilisateur"],
                'email'   => $email,
                'mode'    => 'token_link',
                'reason'  => ($resultat === -1) ? 'mail_params' : 'mail_unknown',
            ]));

            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $msgErreur = $resultat === -1
                ? "<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreurs de paramètres</b></label>"
                : "<br><label><b>Erreur : Le mail n'a pas pu être envoyé, erreur indéterminée</b></label>";
            $this->vue->addToCorps(new Vue_AfficherMessage($msgErreur));
        } else {
            $GLOBALS['logger']->info('auth.password_reset.sent', log_ctx([
                'user_id' => (int) $utilisateur["idUtilisateur"],
                'email'   => $email,
                'mode'    => 'token_link',
                'expires' => $expiration->format(DATE_ATOM),
            ]));

            $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
            $this->vue->addToCorps(new Vue_AfficherMessage("<br><label><b>Mail envoyé. Le lien de réinitialisation expire le " . $expirationTexte . ".</b></label>"));
        }

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function reinitmdp(Request $request, Response $response, array $args): Response
    {
        $this->init();

        $GLOBALS['logger']->info('page.view', log_ctx([
            'page' => 'visiteur.password_reset_form'
        ]));

        $this->vue->addToCorps(new Vue_Mail_ReinitMdp());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function submitModifMDPForce(Request $request, Response $response, array $args): Response
    {
        $this->init();

        // Session requise pour modifier le MDP imposé
        if (!isset($_SESSION["idUtilisateur"]) || empty($_SESSION["idUtilisateur"])) {
            $GLOBALS['logger']->warning('auth.password_change.denied', log_ctx([
                'reason' => 'session_missing'
            ]));

            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Session expirée. Veuillez vous reconnecter."));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $GLOBALS['logger']->info('auth.password_change.attempt', log_ctx([
            'user_id' => (int) $_SESSION["idUtilisateur"]
        ]));

        if ($_REQUEST["NouveauPassword"] == $_REQUEST["ConfirmPassword"]) {
            $this->vue->setEntete(new Vue_Structure_Entete());

            // Vérifie la complexité du nouveau mot de passe
            $bits = CalculComplexiteMdp($_REQUEST["NouveauPassword"]);
            if ($bits < 90) {
                $GLOBALS['logger']->warning('auth.password_change.rejected', log_ctx([
                    'user_id' => (int) $_SESSION["idUtilisateur"],
                    'reason'  => 'weak_password',
                    'bits'    => (int) $bits,
                ]));

                $this->vue->addToCorps(new Vue_Utilisateur_Changement_MDPForce("<label><b>Complexité insuffisante (" . $bits . " bits). Minimum requis : 90 bits.</b></label>"));
                $response->getBody()->write($this->vue->donneStr());
                return $response;
            }

            Modele_Utilisateur::Utilisateur_Modifier_motDePasse($_SESSION["idUtilisateur"], $_REQUEST["NouveauPassword"]);
            Modele_Utilisateur::Utilisateur_Modifier_DoitChangerMdp($_SESSION["idUtilisateur"], (int) 0);

            $GLOBALS['logger']->info('auth.password_change.success', log_ctx([
                'user_id' => (int) $_SESSION["idUtilisateur"]
            ]));

            $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);

            if ($utilisateur["aAccepteRGPD"] == 1) {
                $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                switch ($utilisateur["idCategorie_utilisateur"]) {
                    case 1:
                    case 2:
                        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue !!"));
                        break;
                    case 6:
                        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
                        $this->vue->addToCorps(new Vue_AfficherMessage("Bienvenue dans l'espace RGPD"));
                        break;
                    case 3:
                        $_SESSION["idEntreprise"] = Modele_Entreprise::Entreprise_Select_Par_IdUtilisateur($_SESSION["idUtilisateur"])["idEntreprise"];
                        return $this->entrepriseController->default($request, $response, $args);
                    case 4:
                        $_SESSION["idSalarie"] = $utilisateur["idUtilisateur"];
                        $_SESSION["idEntreprise"] = Modele_Salarie::Salarie_Select_byId($_SESSION["idUtilisateur"])["idEntreprise"];
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
            $GLOBALS['logger']->warning('auth.password_change.rejected', log_ctx([
                'user_id' => (int) $_SESSION["idUtilisateur"],
                'reason'  => 'password_mismatch'
            ]));

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
            // l'utilisateur était connecté mais ouvre l'URL de connexion => logout
            $GLOBALS['logger']->info('session.logout', log_ctx([
                'reason'  => 'visitor_opened_login_page',
                'user_id' => (int) ($_SESSION["idUtilisateur"] ?? 0),
            ]));

            session_destroy();
            unset($_SESSION);
        }

        if (isset($_REQUEST["compte"]) and isset($_REQUEST["password"])) {
            // reset 2FA pending
            if (isset($_SESSION["2fa_pending"])) {
                unset($_SESSION["2fa_pending"]);
            }

            $loginSaisi = (string) $_REQUEST["compte"];
            $GLOBALS['logger']->info('auth.login.attempt', log_ctx([
                'login' => $loginSaisi
            ]));

            // Anti-bruteforce: 5 tentatives erronées -> blocage 2 minutes
            $nbEchecsRecents = Modele_HistoriqueConnexion::HistoriqueConnexion_NombreEchecsRecents($loginSaisi, 120);
            if ($nbEchecsRecents >= 5) {
                $GLOBALS['logger']->warning('auth.login.blocked', log_ctx([
                    'login' => $loginSaisi,
                    'window_sec' => 120,
                    'reason' => 'too_many_failures'
                ]));

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

                    $motDePasseSaisi = (string) $_REQUEST["password"];
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
                        Modele_Utilisateur::Utilisateur_SupprimerMotDePasseTemporaire($utilisateur["idUtilisateur"]);

                        $GLOBALS['logger']->info('auth.login.success', log_ctx([
                            'login'   => $loginSaisi,
                            'user_id' => (int) $utilisateur["idUtilisateur"],
                        ]));

                        $facteurActif = Modele_FacteurAuthentification::Avoir2FA_SelectParUtilisateur((int) $utilisateur["idUtilisateur"]);
                        if ($facteurActif !== null) {
                            $facteurId = (int) $facteurActif["idFacteurAuthentification"];
                            switch ($facteurId) {
                                case 1: // Mail
                                    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                                    $miseAJour = Modele_FacteurAuthentification::Avoir2FA_MettreAJourValeur((int) $utilisateur["idUtilisateur"], $code);
                                    if (!$miseAJour) {
                                        $GLOBALS['logger']->error('auth.2fa.challenge_failed', log_ctx([
                                            'user_id' => (int) $utilisateur["idUtilisateur"],
                                            'factor'  => 'mail',
                                            'reason'  => 'db_update_failed'
                                        ]));

                                        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Erreur lors de la generation du code 2FA. Veuillez reessayer."));
                                    } else {
                                        $messageMail = "<p>Bonjour,</p><p>Votre code de verification est : <strong>" . $code . "</strong>.</p><p>Ce code est valide pour une seule connexion.</p>";
                                        $resultatMail = envoyerMail("administration@cafe.local", "Administrateur cafe", $utilisateur["login"], $utilisateur["login"], "Code de verification", $messageMail);
                                        if ($resultatMail !== 1) {
                                            Modele_FacteurAuthentification::Avoir2FA_MettreAJourValeur((int) $utilisateur["idUtilisateur"], '');

                                            $GLOBALS['logger']->warning('auth.2fa.challenge_failed', log_ctx([
                                                'user_id' => (int) $utilisateur["idUtilisateur"],
                                                'factor'  => 'mail',
                                                'reason'  => 'mail_send_failed'
                                            ]));

                                            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Impossible d'envoyer le code 2FA par mail. Veuillez reessayer."));
                                        } else {
                                            $_SESSION["2fa_pending"] = [
                                                "idUtilisateur" => (int) $utilisateur["idUtilisateur"],
                                                "idCategorie_utilisateur" => (int) $utilisateur["idCategorie_utilisateur"],
                                                "facteur" => $facteurId,
                                                "login" => $utilisateur["login"],
                                            ];

                                            $GLOBALS['logger']->info('auth.2fa.challenge_sent', log_ctx([
                                                'user_id' => (int) $utilisateur["idUtilisateur"],
                                                'factor'  => 'mail'
                                            ]));

                                            $messageFA = "<p>Un code a usage unique vient d'etre envoye a l'adresse <strong>{".$utilisateur["login"]."}</strong>. Entrez-le ci-dessous pour finaliser votre connexion.</p>";
                                            $this->vue->addToCorps(new Vue_Connexion_Second_Facteur($messageFA));
                                        }
                                    }
                                    break;

                                case 2: // Application d'authentification
                                    $_SESSION["2fa_pending"] = [
                                        "idUtilisateur" => (int) $utilisateur["idUtilisateur"],
                                        "idCategorie_utilisateur" => (int) $utilisateur["idCategorie_utilisateur"],
                                        "facteur" => $facteurId,
                                        "login" => $utilisateur["login"],
                                    ];

                                    $GLOBALS['logger']->info('auth.2fa.challenge_sent', log_ctx([
                                        'user_id' => (int) $utilisateur["idUtilisateur"],
                                        'factor'  => 'totp_app'
                                    ]));

                                    $messageFA = "<p>Veuillez vous connecter à votre application d'authentification et saisir le code à 6 chiffres proposé</p>";
                                    $this->vue->addToCorps(new Vue_Connexion_Second_Facteur($messageFA));
                                    break;

                                default:
                                    $GLOBALS['logger']->warning('auth.2fa.challenge_failed', log_ctx([
                                        'user_id' => (int) $utilisateur["idUtilisateur"],
                                        'factor'  => 'unknown',
                                        'reason'  => 'unknown_factor'
                                    ]));

                                    $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Type de deuxieme facteur inconnu."));
                                    break;
                            }

                            $response->getBody()->write($this->vue->donneStr());
                            return $response;
                        }

                        return $this->finaliserConnexion($utilisateur, $request, $response, $args);
                    } else {
                        // Enregistre échec (déjà en BDD)
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        Modele_HistoriqueConnexion::HistoriqueConnexion_EnregistrerTentative($loginSaisi, false, $utilisateur["idUtilisateur"], $ip, $ua);

                        $GLOBALS['logger']->warning('auth.login.failure', log_ctx([
                            'login'   => $loginSaisi,
                            'user_id' => (int) $utilisateur["idUtilisateur"],
                            'reason'  => $motDePasseTemporaireExpireSaisi ? 'temp_password_expired' : 'bad_password'
                        ]));

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
                    $GLOBALS['logger']->warning('auth.login.failure', log_ctx([
                        'login'   => $loginSaisi,
                        'user_id' => (int) ($utilisateur["idUtilisateur"] ?? 0),
                        'reason'  => 'account_disabled'
                    ]));

                    $msgError = "Compte désactivé";
                    $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
                }
            } else {
                // échec login inexistant (déjà en BDD)
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                Modele_HistoriqueConnexion::HistoriqueConnexion_EnregistrerTentative($loginSaisi, false, null, $ip, $ua);

                $GLOBALS['logger']->warning('auth.login.failure', log_ctx([
                    'login'  => $loginSaisi,
                    'reason' => 'user_not_found'
                ]));

                $nbEchecs = Modele_HistoriqueConnexion::HistoriqueConnexion_NombreEchecsRecents($loginSaisi, 120);
                $restantes = max(0, 5 - $nbEchecs);
                $msgError = $restantes > 0
                    ? "Identification invalide. Tentatives restantes avant blocage: " . $restantes
                    : "Identification invalide. Compte temporairement bloqué (2 minutes).";
                $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
            }
        } else {
            $GLOBALS['logger']->warning('auth.login.failure', log_ctx([
                'reason' => 'missing_fields'
            ]));

            $msgError = "Identification incomplete";
            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client($msgError));
        }

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function verifier2FA(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $attente = $_SESSION["2fa_pending"] ?? null;

        if (!is_array($attente)) {
            $GLOBALS['logger']->warning('auth.2fa.failure', log_ctx([
                'reason' => 'no_pending_session'
            ]));

            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Session 2FA expiree. Veuillez vous reconnecter."));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $codeSaisi = trim((string) ($_REQUEST["code2FA"] ?? ""));

        $GLOBALS['logger']->info('auth.2fa.attempt', log_ctx([
            'user_id' => (int) ($attente["idUtilisateur"] ?? 0),
            'factor'  => (int) ($attente["facteur"] ?? 0),
        ]));

        if ($codeSaisi === "" || !preg_match('/^[0-9]{6}$/', $codeSaisi)) {
            $GLOBALS['logger']->warning('auth.2fa.failure', log_ctx([
                'user_id' => (int) ($attente["idUtilisateur"] ?? 0),
                'factor'  => (int) ($attente["facteur"] ?? 0),
                'reason'  => 'bad_format'
            ]));

            $this->vue->addToCorps(new Vue_Connexion_Second_Facteur($attente["login"] ?? "", "Le code doit contenir 6 chiffres."));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $enregistrement = Modele_FacteurAuthentification::Avoir2FA_SelectParUtilisateur((int) $attente["idUtilisateur"]);
        if ($enregistrement === null || (int) $enregistrement["idFacteurAuthentification"] !== (int) $attente["facteur"]) {
            $GLOBALS['logger']->warning('auth.2fa.failure', log_ctx([
                'user_id' => (int) ($attente["idUtilisateur"] ?? 0),
                'factor'  => (int) ($attente["facteur"] ?? 0),
                'reason'  => 'factor_missing_or_changed'
            ]));

            unset($_SESSION["2fa_pending"]);
            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Le deuxieme facteur n'est plus disponible. Veuillez recommencer la connexion."));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        switch ($_SESSION["2fa_pending"]["facteur"]) {
            case 1: // Mail
                if ($codeSaisi !== (string) ($enregistrement["valeur"] ?? "")) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    Modele_HistoriqueConnexion::HistoriqueConnexion_EnregistrerTentative($attente["login"] ?? "", false, (int) $attente["idUtilisateur"], $ip, $ua);

                    $GLOBALS['logger']->warning('auth.2fa.failure', log_ctx([
                        'user_id' => (int) $attente["idUtilisateur"],
                        'factor'  => 1,
                        'reason'  => 'code_incorrect'
                    ]));

                    $this->vue->addToCorps(new Vue_Connexion_Second_Facteur($attente["login"] ?? "", "Code incorrect. Merci de reessayer."));
                    $response->getBody()->write($this->vue->donneStr());
                    return $response;
                }

                Modele_FacteurAuthentification::Avoir2FA_MettreAJourValeur((int) $attente["idUtilisateur"], '');
                break;

            case 2: // Application d'authentification (TOTP)
                $secret = trim($enregistrement["valeur"]);
                date_default_timezone_set('Europe/Paris');

                $totp = TOTP::create(
                    $secret,
                    30,
                    'sha1',
                    6
                );
                $totp->setIssuer("Cafe.local");

                // NOTE: on ne renvoie pas d'info de debug à l'utilisateur (sécurité),
                // mais on peut logger un peu (sans secret ni code)
                if (!$totp->verify($codeSaisi, null, 1)) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    Modele_HistoriqueConnexion::HistoriqueConnexion_EnregistrerTentative($attente["login"] ?? "", false, (int) $attente["idUtilisateur"], $ip, $ua);

                    $GLOBALS['logger']->warning('auth.2fa.failure', log_ctx([
                        'user_id' => (int) $attente["idUtilisateur"],
                        'factor'  => 2,
                        'reason'  => 'code_incorrect'
                    ]));

                    $this->vue->addToCorps(new Vue_Connexion_Second_Facteur($attente["login"] ?? "", "Code incorrect. Merci de reessayer."));
                    $response->getBody()->write($this->vue->donneStr());
                    return $response;
                }
                break;

            default:
                $GLOBALS['logger']->warning('auth.2fa.failure', log_ctx([
                    'user_id' => (int) ($attente["idUtilisateur"] ?? 0),
                    'factor'  => (int) ($attente["facteur"] ?? 0),
                    'reason'  => 'unknown_factor'
                ]));

                unset($_SESSION["2fa_pending"]);
                $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Type de deuxieme facteur inconnu. Veuillez recommencer la connexion."));
                $response->getBody()->write($this->vue->donneStr());
                return $response;
        }

        $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId((int) $attente["idUtilisateur"]);
        unset($_SESSION["2fa_pending"]);

        if ($utilisateur === false || $utilisateur === null) {
            $GLOBALS['logger']->warning('auth.2fa.failure', log_ctx([
                'user_id' => (int) ($attente["idUtilisateur"] ?? 0),
                'reason'  => 'user_missing_after_2fa'
            ]));

            $this->vue->addToCorps(new Vue_Connexion_Formulaire_client("Utilisateur introuvable. Veuillez recommencer la connexion."));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $GLOBALS['logger']->info('auth.2fa.success', log_ctx([
            'user_id' => (int) $attente["idUtilisateur"],
            'factor'  => (int) $attente["facteur"]
        ]));

        return $this->finaliserConnexion($utilisateur, $request, $response, $args);
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();

        if (isset($_SESSION["idUtilisateur"])) {
            $GLOBALS['logger']->info('session.logout', log_ctx([
                'reason'  => 'visitor_opened_login_page',
                'user_id' => (int) ($_SESSION["idUtilisateur"] ?? 0),
            ]));
            session_destroy();
            unset($_SESSION);
        }

        $GLOBALS['logger']->info('page.view', log_ctx([
            'page' => 'visiteur.login_form'
        ]));

        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}





