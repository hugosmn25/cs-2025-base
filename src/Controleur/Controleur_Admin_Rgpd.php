<?php
declare(strict_types=1);
namespace App\Controleur;

use App\Utilitaire\Vue;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Admin_Rgpd_Hub;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue_Structure_Entete;
use App\Vue\Vue_Admin_Rgpd_Finalites;
use App\Modele\Modele_FinalitesConsentement;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Admin_Rgpd
{
    private Vue $vue;

    private function estGestionnaireRgpd(): bool
    {
        return isset($_SESSION['idCategorie_utilisateur']) && (int)$_SESSION['idCategorie_utilisateur'] === 6;
    }

    private function refuserAcces(Response $response): Response
    {
        $this->vue->addToCorps(new Vue_AfficherMessage("<div style='color:red'>Acc�s RGPD r�serv� au gestionnaire RGPD.</div>"));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }


    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    private function init(): void
    {
        $this->vue->setEntete(new Vue_Structure_Entete());
        if (isset($_SESSION['idCategorie_utilisateur'])) {
            $this->vue->setMenu(new Vue_Menu_Administration((int)$_SESSION['idCategorie_utilisateur']));
        }
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $this->vue->addToCorps(new Vue_Admin_Rgpd_Hub());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function finalites(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $liste = Modele_FinalitesConsentement::FinalitesConsentement_Select_All();
        $this->vue->addToCorps(new Vue_Admin_Rgpd_Finalites($liste));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function politique(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $courante = \App\Modele\Modele_VersionsPolitique::VersionsPolitique_Select_Courante();
        $base = date('Y-m-d');
        $suggest = $base;
        $i = 2;
        while (\App\Modele\Modele_VersionsPolitique::VersionsPolitique_Select_ByCode($suggest)) {
            $suggest = $base.'-'.($i++);
            if ($i > 50) break;
        }
        $this->vue->addToCorps(new \App\Vue\Vue_Admin_Rgpd_Politique($courante, $suggest));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function historique(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $evenements = \App\Modele\Modele_EvenementsConsentement::EvenementsConsentement_Select_All();
        $this->vue->addToCorps(new \App\Vue\Vue_Admin_Rgpd_Historique($evenements));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function finalitesAjouter(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $nom = trim($_REQUEST['nom'] ?? '');
        if ($nom === '') {
            $this->vue->addToCorps(new Vue_AfficherMessage("<div style='color:red'>Le nom de la finalitǸ est requis.</div>"));
        } else {
            Modele_FinalitesConsentement::FinalitesConsentement_Ajouter($nom, 1);
            $this->vue->addToCorps(new Vue_AfficherMessage("FinalitǸ ajoutǸe."));
        }
        $liste = Modele_FinalitesConsentement::FinalitesConsentement_Select_All();
        $this->vue->addToCorps(new Vue_Admin_Rgpd_Finalites($liste));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function finalitesRenommer(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $id = (int)($args['id'] ?? ($_REQUEST['id'] ?? 0));
        $nom = trim($_REQUEST['nom'] ?? '');
        if ($id <= 0 || $nom === '') {
            $this->vue->addToCorps(new Vue_AfficherMessage("<div style='color:red'>Param��tres invalides.</div>"));
        } else {
            Modele_FinalitesConsentement::FinalitesConsentement_MAJ($id, $nom);
            $this->vue->addToCorps(new Vue_AfficherMessage("FinalitǸ renommǸe."));
        }
        $liste = Modele_FinalitesConsentement::FinalitesConsentement_Select_All();
        $this->vue->addToCorps(new Vue_Admin_Rgpd_Finalites($liste));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function finalitesActiver(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $id = (int)($args['id'] ?? 0);
        if ($id > 0) {
            Modele_FinalitesConsentement::FinalitesConsentement_Activer($id);
        }
        $liste = Modele_FinalitesConsentement::FinalitesConsentement_Select_All();
        $this->vue->addToCorps(new Vue_Admin_Rgpd_Finalites($liste));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function finalitesDesactiver(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $id = (int)($args['id'] ?? 0);
        if ($id > 0) {
            Modele_FinalitesConsentement::FinalitesConsentement_Desactiver($id);
        }
        $liste = Modele_FinalitesConsentement::FinalitesConsentement_Select_All();
        $this->vue->addToCorps(new Vue_Admin_Rgpd_Finalites($liste));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function politiqueAjouter(Request $request, Response $response, array $args): Response
    {
        $this->init();
        if (!$this->estGestionnaireRgpd()) {
            return $this->refuserAcces($response);
        }

        $code = trim($_REQUEST['code_version'] ?? '');
        $contenu = trim($_REQUEST['contenu'] ?? '');
        if ($code === '' || $contenu === '') {
            $this->vue->addToCorps(new Vue_AfficherMessage("<div style='color:red'>Code de version et contenu sont requis.</div>"));
        } else {
            $hash = hash('sha256', $contenu);
            $publieLe = date('Y-m-d H:i:s');
            $id = \App\Modele\Modele_VersionsPolitique::VersionsPolitique_Ajouter($code, $contenu, $hash, $publieLe, 0);
            if ($id !== false) {
                \App\Modele\Modele_VersionsPolitique::VersionsPolitique_DefinirCourante((int)$id);
                $this->vue->addToCorps(new Vue_AfficherMessage("Version ajoutǸe et dǸfinie courante."));
            } else {
                $this->vue->addToCorps(new Vue_AfficherMessage("<div style='color:red'>Erreur lors de l'ajout.</div>"));
            }
        }
        $courante = \App\Modele\Modele_VersionsPolitique::VersionsPolitique_Select_Courante();
        $base = date('Y-m-d');
        $suggest = $base;
        $i = 2;
        while (\App\Modele\Modele_VersionsPolitique::VersionsPolitique_Select_ByCode($suggest)) {
            $suggest = $base.'-'.($i++);
            if ($i > 50) break;
        }
        $this->vue->addToCorps(new \App\Vue\Vue_Admin_Rgpd_Politique($courante, $suggest));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}

