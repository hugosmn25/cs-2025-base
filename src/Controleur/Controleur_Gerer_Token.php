<?php

namespace App\Controleur;

use App\Modele\Modele_Utilisateur;
use App\Modele\Modele_Token;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Connexion_Formulaire_client;
use App\Vue\Vue_Mail_ChoisirNouveauMdp;
use App\Vue\Vue_Structure_Entete;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use function App\Fonctions\CalculComplexiteMdp;

class Controleur_Gerer_Token
{
    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    public function init(): void
    {
        $this->vue->setEntete(new Vue_Structure_Entete());
    }

    public function choixmdp(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $tokenValeur = $_REQUEST["token"] ?? '';
        $mdp1 = $_REQUEST["mdp1"] ?? '';
        $mdp2 = $_REQUEST["mdp2"] ?? '';

        if ($tokenValeur === '') {
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Token manquant. Veuillez redemander un lien de réinitialisation.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $tokenBDD = Modele_Token::Token_Select($tokenValeur);
        if (!$tokenBDD) {
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Token invalide ou inconnu.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        try {
            $expiration = new \DateTimeImmutable($tokenBDD["dateFin"]);
        } catch (\Exception $e) {
            $expiration = null;
        }
        $now = new \DateTimeImmutable('now');
        if ($expiration === null || $expiration <= $now) {
            Modele_Token::Token_SupprimerParValeur($tokenValeur);
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Token expiré. Veuillez redemander un lien de réinitialisation.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        if ((int) $tokenBDD["codeAction"] !== 1) {
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Action non supportée pour ce token.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        if ($mdp1 !== $mdp2) {
            $this->vue->addToCorps(new Vue_Mail_ChoisirNouveauMdp($tokenValeur, "<label><b>Les mots de passe ne sont pas identiques.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $bits = CalculComplexiteMdp($mdp1);
        if ($bits < 90) {
            $this->vue->addToCorps(new Vue_Mail_ChoisirNouveauMdp($tokenValeur, "<label><b>Complexité insuffisante (" . $bits . " bits). Minimum requis : 90 bits.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        Modele_Utilisateur::Utilisateur_Modifier_motDePasse((int) $tokenBDD["idUtilisateur"], $mdp1);
        Modele_Utilisateur::Utilisateur_DoitChangerMdp((int) $tokenBDD["idUtilisateur"], 0);
        Modele_Token::Token_SupprimerParUtilisateur((int) $tokenBDD["idUtilisateur"], 1);

        $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Votre mot de passe a été mis à jour. Vous pouvez désormais vous connecter avec votre nouveau mot de passe.</b></label>"));
        $this->vue->addToCorps(new Vue_Connexion_Formulaire_client());
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $tokenValeur = $args["token"] ?? ($_REQUEST["token"] ?? '');
        if ($tokenValeur === '') {
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Token manquant. Veuillez redemander un lien de réinitialisation.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        $token = Modele_Token::Token_Select($tokenValeur);
        if (!$token) {
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Token invalide. Veuillez redemander un lien de réinitialisation.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }
        try {
            $expiration = new \DateTimeImmutable($token["dateFin"]);
        } catch (\Exception $e) {
            $expiration = null;
        }
        $now = new \DateTimeImmutable('now');
        if ($expiration === null || $expiration <= $now) {
            Modele_Token::Token_SupprimerParValeur($token["valeur"]);
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Token expiré. Veuillez redemander un lien de réinitialisation.</b></label>"));
            $response->getBody()->write($this->vue->donneStr());
            return $response;
        }

        switch ((int) $token["codeAction"]) {
            case 1:
                $this->vue->addToCorps(new Vue_Mail_ChoisirNouveauMdp($token["valeur"]));
                break;
            default:
                $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Action non supportée pour ce token.</b></label>"));
                break;
        }

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
