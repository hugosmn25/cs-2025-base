<?php
//error_log("page debut");
session_start();
include_once "../vendor/autoload.php";

use Slim\Factory\AppFactory;
use App\Modele\Modele_Token;
use App\Utilitaire\Vue;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_Entete;


//Page appelée pour les utilisateurs publics


$Vue = new Vue();
$Vue->setEntete(new Vue_Structure_Entete());
//Charge le gestionnaire de vue

// Création de l'application Slim
$app = AppFactory::create();

//chargement des différents controleurs
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


//Pour éviter d'être bloqué à cause des sesssions, si l'utilisateur retourne à l'URL de connexion, il est déconnecté
if (isset($_SESSION["idCategorie_utilisateur"])) {
    $typeConnexion = $_SESSION["idCategorie_utilisateur"];
} else {
    $typeConnexion = "0";
}


switch ((int) $typeConnexion) {
    case 0:

        $app->get('/Gerer_Rgpd', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/valider', [$rgpdController, 'validerRGPD']);
        $app->post('/Gerer_Rgpd/validerRGPD', [$rgpdController, 'validerRGPD']);

        $app->get('/', [$visiteurController, 'default']);

        $app->post('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->post('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/reinitmdp', [$visiteurController, 'reinitmdp']);
        $app->post('/reinitmdpconfirm', [$visiteurController, 'reinitmdpconfirm']);
        $app->get('/visiteur/reinitmdp', [$visiteurController, 'reinitmdp']);
        $app->post('/visiteur/reinitmdpconfirm', [$visiteurController, 'reinitmdpconfirm']);
        $app->post('/visiteur/submitModifMDPForce', [$visiteurController, 'submitModifMDPForce']);
        $app->get('/visiteur', [$visiteurController, 'default']);

        break;

    case 1:
    case 2:

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
        $app->get('/Gerer_catalogue/boutonCategorie/{idCategorie}', [$catalogueBackController, 'boutonCategorie']);
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
        $app->post('/Gerer_monCompte/submitModifMDP', [$monCompteBackController, 'submitModifMDP']);
        $app->get('/Gerer_monCompte/SeDeconnecter', [$monCompteBackController, 'SeDeconnecter']);
        $app->post('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/visiteur/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->get('/SeConnecter', [$visiteurController, 'SeConnecter']);
        $app->post('/visiteur/submitModifMDPForce', [$visiteurController, 'submitModifMDPForce']);


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
        $app->post('/Gerer_MonCompte_Salarie/submitModifMDP', [$monCompteSalarieController, 'submitModifMDP']);
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

        $app->get('/Gerer_Entreprise/ChangerMDPEntreprise', [$entrepriseController, 'ChangerMDPEntreprise']);
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
$app->run();
