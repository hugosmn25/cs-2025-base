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
use App\Modele\Modele_FinalitesConsentement;
use App\Modele\Modele_VersionsPolitique;
use App\Modele\Modele_Consentements;
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
                // Enregistrer le refus global RGPD avant de déconnecter
                if (isset($_SESSION["idUtilisateur"])) {
                    $idUserRefus = (int)$_SESSION["idUtilisateur"];
                    Modele_Utilisateur::Utilisateur_UpdateRgpd($idUserRefus, 0, $_SERVER['REMOTE_ADDR'] ?? '');
                    $versionId = (int)(Modele_VersionsPolitique::VersionsPolitique_Select_Courante()['id'] ?? 0);
                    $rgpdGlobalNom = 'RGPD global';
                    $f = Modele_FinalitesConsentement::FinalitesConsentement_Select_ByNom($rgpdGlobalNom);
                    if ($f === false) {
                        Modele_FinalitesConsentement::FinalitesConsentement_Ajouter($rgpdGlobalNom, 0);
                        $f = Modele_FinalitesConsentement::FinalitesConsentement_Select_ByNom($rgpdGlobalNom);
                    }
                    if ($f !== false && $versionId > 0) {
                        $finaliteIdGlobal = (int)$f['id'];
                        $idConsentGlobal = Modele_Consentements::Consentements_Ajouter($idUserRefus, $finaliteIdGlobal, 'refuse', $versionId, $_SERVER['REMOTE_ADDR'] ?? null);
                        if ($idConsentGlobal !== false) {
                            $snapshot = $versionId.'|'.$finaliteIdGlobal.'|refuse|'.$idUserRefus;
                            $hash = hash('sha256', $snapshot);
                            \App\Modele\Modele_EvenementsConsentement::EvenementsConsentement_Ajouter((int)$idConsentGlobal, 'refus', $versionId, $hash, $_SERVER['REMOTE_ADDR'] ?? null, $idUserRefus);
                        }
                    }
                }
                session_destroy();
                unset($_SESSION);
                $this->vue->setEntete(new Vue_Structure_Entete());
                $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
            } else {


                $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
                if ($utilisateur != null) {
                    // Enregistrer les consentements par finalité si fournis
                    $finalitesPost = $_REQUEST['finalite'] ?? [];
                    $versionId = isset($_REQUEST['version_politique_id']) ? (int)$_REQUEST['version_politique_id'] : 0;
                    if ($versionId === 0) {
                        $versionId = (int)(Modele_VersionsPolitique::VersionsPolitique_Select_Courante()['id'] ?? 0);
                    }
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    if (is_array($finalitesPost) && $versionId > 0) {
                        foreach ($finalitesPost as $finaliteId => $statut) {
                            $finaliteId = (int)$finaliteId;
                            if (!in_array($statut, ['accorde','refuse','retire'], true)) continue;
                            $last = Modele_Consentements::Consentements_Select_Dernier_ByUtilisateur_Finalite($utilisateur["idUtilisateur"], $finaliteId);
                            $needInsert = false;
                            $eventType = 'mise_a_jour';
                            if (!$last) {
                                $needInsert = true;
                                $eventType = ($statut === 'accorde') ? 'accord' : (($statut === 'refuse') ? 'refus' : 'mise_a_jour');
                            } else {
                                if ($last['statut'] !== $statut || (int)$last['version_politique_id'] !== $versionId) {
                                    $needInsert = true;
                                    if ($last['statut'] === 'accorde' && $statut === 'refuse') $eventType = 'retrait';
                                    elseif ($statut === 'accorde') $eventType = 'accord';
                                    elseif ($statut === 'refuse') $eventType = 'refus';
                                    else $eventType = 'mise_a_jour';
                                }
                            }
                            if ($needInsert) {
                                $idConsent = Modele_Consentements::Consentements_Ajouter((int)$utilisateur['idUtilisateur'], $finaliteId, $statut, $versionId, $ip);
                                if ($idConsent !== false) {
                                    $snapshot = $versionId.'|'.$finaliteId.'|'.$statut.'|'.$utilisateur['idUtilisateur'];
                                    $hash = hash('sha256', $snapshot);
                                    \App\Modele\Modele_EvenementsConsentement::EvenementsConsentement_Ajouter((int)$idConsent, $eventType, $versionId, $hash, $ip, (int)$utilisateur['idUtilisateur']);
                                }
                            }
                        }
                    }
                    Modele_Utilisateur::Utilisateur_UpdateRgpd($utilisateur["idUtilisateur"], $_REQUEST["accepterRGPD"], $_SERVER['REMOTE_ADDR']);

                    // Enregistrer aussi l'action globale RGPD dans les tables de consentement/événements
                    $rgpdGlobalNom = 'RGPD global';
                    $f = Modele_FinalitesConsentement::FinalitesConsentement_Select_ByNom($rgpdGlobalNom);
                    if ($f === false) {
                        // Crée en inactif pour ne pas l'afficher dans l'UI
                        Modele_FinalitesConsentement::FinalitesConsentement_Ajouter($rgpdGlobalNom, 0);
                        $f = Modele_FinalitesConsentement::FinalitesConsentement_Select_ByNom($rgpdGlobalNom);
                    }
                    if ($f !== false) {
                        $finaliteIdGlobal = (int)$f['id'];
                        $statutGlobal = ((int)$_REQUEST['accepterRGPD'] === 1) ? 'accorde' : 'refuse';
                        // S'assure d'avoir une version de politique courante
                        if (!isset($versionId) || $versionId <= 0) {
                            $versionId = (int)(Modele_VersionsPolitique::VersionsPolitique_Select_Courante()['id'] ?? 0);
                        }
                        if ($versionId > 0) {
                            $idConsentGlobal = Modele_Consentements::Consentements_Ajouter((int)$utilisateur['idUtilisateur'], $finaliteIdGlobal, $statutGlobal, $versionId, $_SERVER['REMOTE_ADDR'] ?? null);
                            if ($idConsentGlobal !== false) {
                                $eventType = ($statutGlobal === 'accorde') ? 'accord' : 'refus';
                                $snapshot = $versionId.'|'.$finaliteIdGlobal.'|'.$statutGlobal.'|'.$utilisateur['idUtilisateur'];
                                $hash = hash('sha256', $snapshot);
                                \App\Modele\Modele_EvenementsConsentement::EvenementsConsentement_Ajouter((int)$idConsentGlobal, $eventType, $versionId, $hash, $_SERVER['REMOTE_ADDR'] ?? null, (int)$utilisateur['idUtilisateur']);
                            }
                        }
                    }
                    $_SESSION["idCategorie_utilisateur"] = $utilisateur["idCategorie_utilisateur"];
                    switch ($utilisateur["idCategorie_utilisateur"]) {
                        case 5:
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
                    $this->vue->addToCorps(new Vue_AfficherMessage("Erreur utilisateur non trouvé"));

                }
            }
        } else {
            $utilisateur = Modele_Utilisateur::Utilisateur_Select_ParId($_SESSION["idUtilisateur"]);
            $politique = Modele_VersionsPolitique::VersionsPolitique_Select_Courante();
            $finalites = Modele_FinalitesConsentement::FinalitesConsentement_Select_Actives();
            $consentsMap = [];
            if ($utilisateur && isset($utilisateur["idUtilisateur"])) {
                foreach ($finalites as $f) {
                    $last = Modele_Consentements::Consentements_Select_Dernier_ByUtilisateur_Finalite($utilisateur["idUtilisateur"], (int)$f['id']);
                    if ($last) { $consentsMap[(int)$f['id']] = $last['statut']; }
                }
            }
            $this->vue->addToCorps(new Vue_ConsentementRGPD($utilisateur, $politique, $finalites, $consentsMap));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

}

