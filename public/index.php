<?php
//error_log("page debut");
session_start();
include_once "../vendor/autoload.php";
include_once "../src/Fonctions/CSRF.php";

use Slim\Factory\AppFactory;
use App\Utilitaire\Vue;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_Entete;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

// Logs (syslog / RFC5424 via syslog collector)
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Formatter\LineFormatter;

// Page appelée pour les utilisateurs publics
genereCSRF();

/**
 * Helpers logs
 */
function get_client_ip(): string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function get_request_id(): string
{
    if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
        return (string) $_SERVER['HTTP_X_REQUEST_ID'];
    }
    return bin2hex(random_bytes(16));
}

function log_ctx(array $extra = []): array
{
    return array_merge([
        'req_id'  => $GLOBALS['req_id'] ?? null,
        'ip'      => get_client_ip(),
        'method'  => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri'     => $_SERVER['REQUEST_URI'] ?? null,
        'ua'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'user_id' => $_SESSION['idUtilisateur'] ?? null,
        'role'    => $_SESSION['idCategorie_utilisateur'] ?? 0,
    ], $extra);
}

$logger = new Logger('cafe-app');

$formatter = new LineFormatter(
    "%message% %context%\n",
    "Y-m-d\TH:i:s.uP",
    true,
    true
);

$useSyslog = extension_loaded('sockets'); // <— clé de la correction

if ($useSyslog) {
    $syslogHost = '127.0.0.1';
    $syslogPort = 514;

    $handler = new SyslogUdpHandler($syslogHost, $syslogPort, LOG_USER, Logger::INFO);
    $handler->setFormatter($formatter);
    $logger->pushHandler($handler);
} else {
    // Fallback fichier (Windows)
    $logDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0750, true);
    }

    $fileHandler = new StreamHandler($logDir . DIRECTORY_SEPARATOR . 'app.log', Logger::INFO);
    $fileHandler->setFormatter($formatter);
    $logger->pushHandler($fileHandler);

    $logger->warning('logger.syslog_disabled', [
        'reason' => 'php extension sockets missing',
        'php_ini' => php_ini_loaded_file()
    ]);
}

$GLOBALS['logger'] = $logger;
$GLOBALS['req_id'] = get_request_id();

$Vue = new Vue();
$Vue->setEntete(new Vue_Structure_Entete());
// Charge le gestionnaire de vue

// Création de l'application Slim
$app = AppFactory::create();

/**
 * Middleware global:
 * - request.start
 * - request.end (status + duration)
 * - unhandled.exception
 */
$app->add(function (Request $request, $handler) use ($logger) {
    $start = microtime(true);

    $logger->info('request.start', log_ctx([
        'route' => (string) $request->getUri()->getPath(),
    ]));

    try {
        $response = $handler->handle($request);
    } catch (\Throwable $e) {
        $logger->error('unhandled.exception', log_ctx([
            'route'     => (string) $request->getUri()->getPath(),
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]));
        throw $e;
    }

    $durationMs = (int) round((microtime(true) - $start) * 1000);
    $logger->info('request.end', log_ctx([
        'route'       => (string) $request->getUri()->getPath(),
        'status_code' => $response->getStatusCode(),
        'duration_ms' => $durationMs,
    ]));

    return $response;
});

// chargement des différents controleurs
$catalogueBackController = new \App\Controleur\Controleur_Gerer_catalogue($Vue);
$catalogueClientController = new \App\Controleur\Controleur_Catalogue_client($Vue);
$commandeBackController = new \App\Controleur\Controleur_Gerer_Commande($Vue);
$commandeClientController = new \App\Controleur\Controleur_Gerer_CommandeClient($Vue);
$panierController = new \App\Controleur\Controleur_Gerer_Panier($Vue);
$entrepriseController = new \App\Controleur\Controleur_Gerer_Entreprise($Vue);
$monCompteBackController = new \App\Controleur\Controleur_Gerer_monCompte($Vue);
$monCompteSalarieController = new \App\Controleur\Controleur_Gerer_MonCompte_Salarie($Vue);
$utilisateurController = new \App\Controleur\Controleur_Gerer_utilisateur($Vue);
$entreprisesPartenairesController = new \App\Controleur\Controleur_Gerer_entreprisesPartenaires($Vue);
$tokenController = new \App\Controleur\Controleur_Gerer_Token($Vue);
$rgpdController = new \App\Controleur\Controleur_Gerer_Rgpd($Vue, $catalogueClientController, $entrepriseController);
$visiteurController = new \App\Controleur\Controleur_visiteur($Vue, $catalogueClientController, $entrepriseController);
$admin_RgpdController = new \App\Controleur\Controleur_Admin_Rgpd($Vue);

$app->get('/reinitmdp/token/', [$tokenController, 'default']);
//$app->get('/visiteur/reinitmdp/', [$tokenController, 'default']);
$app->post('/choixmdp', [$tokenController, 'choixmdp']);
$app->post('/visiteur/choixmdp', [$tokenController, 'choixmdp']);

// Pour éviter d'être bloqué à cause des sesssions, si l'utilisateur retourne à l'URL de connexion, il est déconnecté
if (isset($_SESSION["idCategorie_utilisateur"])) {
    $typeConnexion = $_SESSION["idCategorie_utilisateur"];
} else {
    $typeConnexion = 0;
}

switch ((int) $typeConnexion) {
    case 0:

        $app->get('/Gerer_Rgpd', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/valider', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/validerRGPD', [$rgpdController, 'validerRGPD']);

        $app->get('/', [$visiteurController, 'default']);

        $app->post('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->post('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/reinitmdp', [$visiteurController, 'reinitmdp']);
        $app->post('/reinitmdpconfirm', [$visiteurController, 'reinitmdpconfirm']);
        $app->get('/visiteur/reinitmdp', [$visiteurController, 'reinitmdp']);
        $app->post('/visiteur/reinitmdpconfirm', [$visiteurController, 'reinitmdpconfirm']);
        $app->post('/reinitmdptoken', [$visiteurController, 'reinitmdpconfirmtoken']);
        $app->post('/visiteur/reinitmdptoken', [$visiteurController, 'reinitmdpconfirmtoken']);
        $app->post('/visiteur/submitModifMDPForce', [$visiteurController, 'submitModifMDPForce']);
        $app->post('/visiteur/verifier2FA', [$visiteurController, 'verifier2FA']);
        $app->get('/visiteur', [$visiteurController, 'default']);

        break;

    case 1:
    case 2:
    case 5:
        $app->get('/', [$visiteurController, 'default']);
        $app->get('/Gerer_Rgpd', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/valider', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/validerRGPD', [$rgpdController, 'validerRGPD']);

        $app->get('/Gerer_Commande', [$commandeBackController, 'Toute']);
        $app->get('/Gerer_Commande/Toute', [$commandeBackController, 'Toute']);
        $app->get('/Gerer_Commande/boutonCategorie/{idEtatCommande}', [$commandeBackController, 'boutonCategorie']);
        $app->get('/Gerer_Commande/VoirDetailCommande/{idCommande}', [$commandeBackController, 'VoirDetailCommande']);
        $app->post('/Gerer_Commande/Signaler_CommandePayee/{idCommande}', [$commandeBackController, 'Signaler_CommandePayee']);
        $app->post('/Gerer_Commande/Signalee_CommandeEnPreparation/{idCommande}', [$commandeBackController, 'Signalee_CommandeEnPreparation']);
        $app->post('/Gerer_Commande/Signalee_CommandeProblemeStock/{idCommande}', [$commandeBackController, 'Signalee_CommandeProblemeStock']);
        $app->post('/Gerer_Commande/Signalee_CommandeEnvoyée/{idCommande}', [$commandeBackController, 'Signalee_CommandeEnvoyée']);
        $app->get('/Gerer_entreprisesPartenaires', [$entreprisesPartenairesController, 'default']);
        $app->get('/Gerer_entreprisesPartenaires/nouveau', [$entreprisesPartenairesController, 'nouveau']);
        $app->post('/Gerer_entreprisesPartenaires/buttonCreer', [$entreprisesPartenairesController, 'buttonCreer']);
        $app->get('/Gerer_entreprisesPartenaires/Modifer', [$entreprisesPartenairesController, 'Modifer']);
        $app->post('/Gerer_entreprisesPartenaires/mettreAJour', [$entreprisesPartenairesController, 'mettreAJour']);
        $app->post('/Gerer_entreprisesPartenaires/DesactiverEntreprise', [$entreprisesPartenairesController, 'DesactiverEntreprise']);
        $app->post('/Gerer_entreprisesPartenaires/ActiverEntreprise', [$entreprisesPartenairesController, 'ActiverEntreprise']); // (Optionnel) réinit MDP entreprise: // $app->post('/Gerer_entreprisesPartenaires/reinitialiserMDP', [$entreprisesPartenairesController, 'rÃ©initialiserMDP']);
        $app->get('/Gerer_utilisateur', [$utilisateurController, 'default']);
        $app->get('/Gerer_utilisateur/nouveau', [$utilisateurController, 'nouveau']);
        $app->post('/Gerer_utilisateur/buttonCreerUtilisateur', [$utilisateurController, 'buttonCreerUtilisateur']);
        $app->get('/Gerer_utilisateur/ModifierUtilisateur/{id}', [$utilisateurController, 'ModifierUtilisateur']);
        $app->post('/Gerer_utilisateur/mettreAJourUtilisateur/{id}', [$utilisateurController, 'mettreAJourUtilisateur']);
        $app->get('/Gerer_utilisateur/DesactiverUtilisateur/{id}', [$utilisateurController, 'DesactiverUtilisateur']);
        $app->get('/Gerer_utilisateur/ActiverUtilisateur/{id}', [$utilisateurController, 'ActiverUtilisateur']); // (Optionnel) si tu veux aussi exposer la réinit MDP par route: //
        $app->post('/Gerer_utilisateur/reinitialiserMDPUtilisateur/{id}', [$utilisateurController, 'reinitialiserMDPUtilisateur']);
        $app->get('/Gerer_catalogue', [$catalogueBackController, 'default']);
        $app->get('/Gerer_catalogue/boutonCategorie/{uuid}', [$catalogueBackController, 'boutonCategorie']);
        $app->get('/Gerer_catalogue/nouveauProduit/{idCategorie}', [$catalogueBackController, 'nouveauProduit']);
        $app->post('/Gerer_catalogue/CreationProduit', [$catalogueBackController, 'CreationProduit']);
        $app->post('/Gerer_catalogue/CategorieAvecProduit', [$catalogueBackController, 'CategorieAvecProduit']);
        $app->get('/Gerer_catalogue/ModifierProduit/{id}', [$catalogueBackController, 'ModifierProduit']);
        $app->post('/Gerer_catalogue/mettreAJourProduit/{id}', [$catalogueBackController, 'mettreAJourProduit']);
        $app->get('/Gerer_catalogue/AjouterCategorie', [$catalogueBackController, 'AjouterCategorie']);
        $app->get('/Gerer_catalogue/ModifierCategorie/{idCategorie}', [$catalogueBackController, 'ModifierCategorie']);
        $app->post('/Gerer_catalogue/mettreAJourCategorie/{idCategorie}', [$catalogueBackController, 'mettreAJourCategorie']);
        $app->get('/Gerer_catalogue/nouvelleCategorie', [$catalogueBackController, 'nouvelleCategorie']);
        $app->post('/Gerer_catalogue/CreerCategorie', [$catalogueBackController, 'CreerCategorie']);
        $app->get('/Gerer_catalogue/DesactiverCategorie/{idCategorie}', [$catalogueBackController, 'DesactiverCategorie']);
        $app->get('/Gerer_catalogue/ActiverCategorie/{idCategorie}', [$catalogueBackController, 'ActiverCategorie']);
        $app->get('/Gerer_catalogue/OuiDesactivation/{idCategorie}', [$catalogueBackController, 'OuiDesactivation']);
        $app->get('/Gerer_catalogue/okRechercher', [$catalogueBackController, 'okRechercher']);
        $app->get('/Gerer_monCompte', [$monCompteBackController, 'default']);
        $app->get('/Gerer_monCompte/changerMDP', [$monCompteBackController, 'changerMDP']);
        $app->get('/Gerer_monCompte/gerer2FA', [$monCompteBackController, 'gerer2FA']);
        $app->post('/Gerer_monCompte/submitModifMDP', [$monCompteBackController, 'submitModifMDP']);
        $app->post('/Gerer_monCompte/definir2FA', [$monCompteBackController, 'definir2FA']);
        $app->post('/Gerer_monCompte/supprimer2FA', [$monCompteBackController, 'supprimer2FA']);
        $app->get('/Gerer_monCompte/SeDeconnecter', [$monCompteBackController, 'SeDeconnecter']);
        $app->post('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->post('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->post('/visiteur/submitModifMDPForce', [$visiteurController, 'submitModifMDPForce']);

        break;

    case 6:
        $app->get('/', [$admin_RgpdController, 'default']);
        $app->get('/Gerer_Rgpd', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/valider', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/validerRGPD', [$rgpdController, 'validerRGPD']);

        $app->post('/Admin_Rgpd/finalites/ajouter', [$admin_RgpdController, 'finalitesAjouter']);
        $app->post('/Admin_Rgpd/finalites/renommer/{id}', [$admin_RgpdController, 'finalitesRenommer']);
        $app->get('/Admin_Rgpd/finalites/activer/{id}', [$admin_RgpdController, 'finalitesActiver']);
        $app->get('/Admin_Rgpd/finalites/desactiver/{id}', [$admin_RgpdController, 'finalitesDesactiver']);
        $app->get('/Admin_Rgpd', [$admin_RgpdController, 'default']);
        $app->get('/Admin_Rgpd/finalites', [$admin_RgpdController, 'finalites']);
        $app->get('/Admin_Rgpd/politique', [$admin_RgpdController, 'politique']);
        $app->post('/Admin_Rgpd/politique/ajouter', [$admin_RgpdController, 'politiqueAjouter']);
        $app->get('/Admin_Rgpd/historique', [$admin_RgpdController, 'historique']);

        $app->get('/Gerer_monCompte', [$monCompteBackController, 'default']);
        $app->get('/Gerer_monCompte/changerMDP', [$monCompteBackController, 'changerMDP']);
        $app->get('/Gerer_monCompte/gerer2FA', [$monCompteBackController, 'gerer2FA']);
        $app->post('/Gerer_monCompte/submitModifMDP', [$monCompteBackController, 'submitModifMDP']);
        $app->post('/Gerer_monCompte/definir2FA', [$monCompteBackController, 'definir2FA']);
        $app->post('/Gerer_monCompte/supprimer2FA', [$monCompteBackController, 'supprimer2FA']);
        $app->get('/Gerer_monCompte/SeDeconnecter', [$monCompteBackController, 'SeDeconnecter']);

        $app->post('/visiteur/submitModifMDPForce', [$visiteurController, 'submitModifMDPForce']);
        $app->post('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->post('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/SeConnecter', [$visiteurController, 'SeConnecter']);

        break;

    case 3:
    case 4:

        $app->get('/Gerer_Rgpd', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/valider', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/validerRGPD', [$rgpdController, 'validerRGPD']);

        $app->get('/Gerer_CommandeClient', [$commandeClientController, 'default']);
        $app->get('/Gerer_CommandeClient/VoirDetailCommande/{idCommande}', [$commandeClientController, 'VoirDetailCommande']);
        $app->post('/Gerer_CommandeClient/Signalee_CommandeReceptionnee/{idCommande}', [$commandeClientController, 'Signalee_CommandeReceptionnee']);
        $app->post('/Gerer_CommandeClient/Signalee_CommandeReceptionneeIncident/{idCommande}', [$commandeClientController, 'Signalee_CommandeReceptionneeIncident']);
        $app->get('/Gerer_CommandeClient/AfficherCommandePDF/{idCommande}', [$commandeClientController, 'AfficherCommandePDF']);
        $app->get('/Gerer_Panier', [$panierController, 'default']);
        $app->post('/Gerer_Panier/diminuerQTT/{idProduit}', [$panierController, 'diminuerQTT']);
        $app->post('/Gerer_Panier/augmenterQTT/{idProduit}', [$panierController, 'augmenterQTT']);
        $app->get('/Gerer_Panier/validerPanier', [$panierController, 'validerPanier']);
        $app->get('/Gerer_MonCompte_Salarie', [$monCompteSalarieController, 'default']);
        $app->get('/Gerer_MonCompte_Salarie/changerMDP', [$monCompteSalarieController, 'changerMDP']);
        $app->get('/Gerer_MonCompte_Salarie/gerer2FA', [$monCompteSalarieController, 'gerer2FA']);
        $app->post('/Gerer_MonCompte_Salarie/submitModifMDP', [$monCompteSalarieController, 'submitModifMDP']);
        $app->post('/Gerer_MonCompte_Salarie/definir2FA', [$monCompteSalarieController, 'definir2FA']);
        $app->post('/Gerer_MonCompte_Salarie/supprimer2FA', [$monCompteSalarieController, 'supprimer2FA']);
        $app->get('/Gerer_MonCompte_Salarie/SeDeconnecter', [$monCompteSalarieController, 'SeDeconnecter']);
        $app->get('/Gerer_Entreprise', [$entrepriseController, 'default']);
        $app->get('/Gerer_Entreprise/infoEntreprise', [$entrepriseController, 'infoEntreprise']);
        $app->get('/Gerer_Entreprise/salariesHabitites', [$entrepriseController, 'salariesHabitites']);
        $app->get('/Gerer_Entreprise/ajouterSalarie', [$entrepriseController, 'ajouterSalarie']);
        $app->post('/Gerer_Entreprise/buttonCreerSalarie', [$entrepriseController, 'buttonCreerSalarie']);
        $app->get('/Gerer_Entreprise/ModiferSalarie/{idSalarie}', [$entrepriseController, 'ModiferSalarie']);
        $app->post('/Gerer_Entreprise/ModiferSalarieValider/{idSalarie}', [$entrepriseController, 'ModiferSalarieValider']);
        $app->post('/Gerer_Entreprise/DesactiverSalarie', [$entrepriseController, 'DesactiverSalarie']);
        $app->post('/Gerer_Entreprise/ActiverSalarie', [$entrepriseController, 'ActiverSalarie']);
        $app->post('/Gerer_Entreprise/submitModifMDP', [$entrepriseController, 'submitModifMDP']);
        $app->post('/Gerer_Entreprise/definir2FA', [$entrepriseController, 'definir2FA']);
        $app->post('/Gerer_Entreprise/supprimer2FA', [$entrepriseController, 'supprimer2FA']);

        $app->get('/Gerer_Entreprise/ChangerMDPEntreprise', [$entrepriseController, 'ChangerMDPEntreprise']);
        $app->get('/Gerer_Entreprise/gerer2FA', [$entrepriseController, 'gerer2FA']);
        $app->get('/Gerer_Entreprise/deconnexionEntreprise', [$entrepriseController, 'deconnexionEntreprise']);
        $app->get('/Gerer_monCompte/deconnexionEntreprise', [$entrepriseController, 'deconnexionEntreprise']);

        $app->get('/Catalogue_client', [$catalogueClientController, 'default']);
        $app->get('/Catalogue_client/boutonCategorie/{idCategorie}', [$catalogueClientController, 'boutonCategorie']);
        $app->get('/Catalogue_client/okRechercher', [$catalogueClientController, 'okRechercher']);
        $app->post('/Catalogue_client/AjoutPanierClient/{idProduit}', [$catalogueClientController, 'AjoutPanierClient']);
        $app->post('/visiteur/submitModifMDPForce', [$visiteurController, 'submitModifMDPForce']);

        $app->post('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->post('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/', [$visiteurController, 'default']);

    default:
}

// Fallback 404: à garder juste avant run()
$app->map(['GET','POST','PUT','DELETE','PATCH'], '/{routes:.+}', function (Request $request, Response $response) {
    $GLOBALS['logger']->warning('route.not_found', log_ctx([
        'route' => (string) $request->getUri()->getPath()
    ]));
    $response->getBody()->write("404");
    return $response->withStatus(404);
});

$app->run();