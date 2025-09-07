<?php

namespace App\Controleur;

use App\Modele\Modele_Utilisateur;
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
        
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        global $tokenBDD;
        switch ($tokenBDD["codeAction"]) {
            case 1:
                $this->vue->addToCorps(new Vue_Mail_ChoisirNouveauMdp($tokenBDD["valeur"]));

                break;
            default:

                break;
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
