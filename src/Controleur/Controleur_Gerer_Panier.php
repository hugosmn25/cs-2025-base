<?php

namespace App\Controleur;

use App\Modele\Modele_Commande;
use App\Modele\Modele_Catalogue;
use App\Modele\Modele_Entreprise;
use App\Vue\Facture_BasPageBrulerie;
use App\Vue\Facture_EnteteBrulerie;
use App\Vue\Vue__CategoriesListe;
use App\Vue\Vue_Menu_Entreprise_Salarie;
use App\Vue\Vue_Panier_Client;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use Spipu\Html2Pdf\Html2Pdf;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Controleur_Gerer_Panier
{
    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }

    public function init(): void
    {
                 $quantiteMenu = Modele_Commande::Panier_Quantite($_SESSION["idEntreprise"]);

        $this->vue->setMenu(new Vue_Menu_Entreprise_Salarie($quantiteMenu));

        //Vue_Entreprise_Client_ Menu(); 
    }

    public function diminuerQTT(Request $request, Response $response, array $args): Response
    {
       
        $idProduit = $args["idProduit"];
        Modele_Commande::Panier_DiminuerQTT_Article($_SESSION["idEntreprise"], $idProduit);
         $this->init();
        $listeArticlePanier = Modele_Commande::Panier_ListeArticle($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticlePanier));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function augmenterQTT(Request $request, Response $response, array $args): Response
    {
        
        $idProduit = $args["idProduit"];
        Modele_Commande::Panier_AugmenterQTT_Article($_SESSION["idEntreprise"], $idProduit);
        $this->init();
        $listeArticlePanier = Modele_Commande::Panier_ListeArticle($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticlePanier));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function validerPanier(Request $request, Response $response, array $args): Response
    {
       // $this->init();
        ob_start();
        $listeArticlePanier = Modele_Commande::Panier_ListeArticle($_SESSION["idEntreprise"]);
        $infoCommande = Modele_Commande::Caddie_Select_ParIdEntreprise($_SESSION["idEntreprise"]);
        $infoEntreprise = Modele_Entreprise::Entreprise_Select_ParId($_SESSION["idEntreprise"]);
        $this->vue->setEntete(new Facture_EnteteBrulerie($infoCommande, $infoEntreprise));
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticlePanier, true));
        $this->vue->setBasDePage(new Facture_BasPageBrulerie($infoCommande, $infoEntreprise));
        echo $this->vue->donneStr();
        Modele_Commande::Commande_Valider_Caddie($infoCommande["id"], $_SESSION["idSalarie"]);
        $content = ob_get_clean();
        $html2pdf = new Html2Pdf('L', 'A4', 'fr');
        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->writeHTML($content);
        $html2pdf->output('facture.pdf');
        exit();
       /* $response->getBody()->write($this->vue->donneStr());
        return $response;*/
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeArticlePanier = Modele_Commande::Panier_ListeArticle($_SESSION["idEntreprise"]);
        $this->vue->addToCorps(new Vue_Panier_Client($listeArticlePanier));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
