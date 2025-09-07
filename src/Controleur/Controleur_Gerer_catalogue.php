<?php

namespace App\Controleur;

use App\Modele\Modele_Catalogue;
use App\Vue\Vue_Menu_Administration;
use App\Vue\Vue__CategoriesListe;
use App\Vue\Vue_Produit_Creation;
use App\Vue\Vue_Categories_Liste;
use App\Vue\Vue_Produit_Tous;
use App\Vue\Vue_AfficherMessage;
use App\Vue\Vue_Demande_Approbation_Desactivation;
use App\Vue\Vue_Categorie_Creation_Modification_;
use App\Vue\Vue_Catalogue_Formulaire;
use App\Vue\Vue_Liste_Categorie;
use App\Vue\Vue_Structure_BasDePage;
use App\Vue\Vue_Structure_Entete;
use App\Utilitaire\Vue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


class Controleur_Gerer_catalogue
{

    private Vue $vue;

    public function __construct(Vue $vue)
    {
        $this->vue = $vue;
    }
    public function init():void
    {
        $this->vue->setEntete(new Vue_Structure_Entete());
        $this->vue->setBasDePage(new Vue_Structure_BasDePage());
        $this->vue->setMenu(new Vue_Menu_Administration($_SESSION["idCategorie_utilisateur"]));
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $this->vue->addToCorps(new Vue_Categories_Liste($listeCategorie, true));
    }

    public function boutonCategorie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idCategorie = (int)($args['idCategorie'] ?? -1);
         
        $listeProduit = Modele_Catalogue::Select_Produit_Select_ParIdCateg($idCategorie);
        $this->vue->addToCorps(new Vue_Produit_Tous($listeProduit, $idCategorie));
        //Vue_Affiche_Liste_Produit_UneCategorie($listeProduit);
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function CreationCategorieAvecProduit(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $listeTVA = Modele_Catalogue::TVA_Select_Tous();
        $fichier_image = "";
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $listeTVA = Modele_Catalogue::TVA_Select_Tous();
        if (isset($_FILES['image_utilisateur']) and $_FILES['image_utilisateur']['error'] == 0) {
            $this->vue->addToCorps(new Vue_AfficherMessage("<label><b>Pour des raisons de sécurité, veuillez resélectionner votre image</b></label>"));
        }
        $this->vue->addToCorps(new Vue_Catalogue_Formulaire(
            $listeCategorie,
            $listeTVA,
            true,
            true,
            "",
            $_REQUEST["nom"],
            $_REQUEST["description"],
            $_REQUEST["resume"],
            $fichier_image,
            $_REQUEST["prixCatalogueHT"],
            $_REQUEST["idCategorie"],
            $_REQUEST["idTVA"],
            0
        ));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function CreationProduit(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $listeTVA = Modele_Catalogue::TVA_Select_Tous();
        $fichier_image = "";
        if (isset($_FILES['image_utilisateur']) and $_FILES['image_utilisateur']['error'] == 0) {
            $fichier_image = basename($_FILES['image_utilisateur']['name']);
            move_uploaded_file($_FILES['image_utilisateur']['tmp_name'], '/image/' . $fichier_image);
        }
        if (isset($_REQUEST["CategorieAvecProduit"])) {
            $idCategorie = Modele_Catalogue::Categorie_Creer(
                $_REQUEST["CategorieAvecProduit"],
                $_REQUEST["DescriptionCategorieAvecProduit"],
                0
            );
        } else {
            $idCategorie = $_REQUEST["idCategorie"];
        }
        $idProduit = Modele_Catalogue::Produit_Creer(
            $_REQUEST["nom"],
            $_REQUEST["description"],
            $_REQUEST["resume"],
            $fichier_image,
            $_REQUEST["prixCatalogueHT"],
            $idCategorie,
            $_REQUEST["idTVA"],
            $_REQUEST["DesactiverProduit"]
        );
        $produit = Modele_Catalogue::Produit_Select_ParId($idProduit);
        Modele_Catalogue::Produit_Update_Ref($produit["libelle"], $produit["nom"], $idProduit);
        // Une fois le produit crée, on lui affiche une page pour savoir si le produit a bien été créé ou non, ainsi qu'un lien pour revenir sur le catalogue
        $this->vue->addToCorps(new Vue_Produit_Creation($idProduit, false, true));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }


    public function CategorieAvecProduit(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $listeTVA = Modele_Catalogue::TVA_Select_Tous();
        $fichier_image = "";
        // Si l'utilisateur veut créer une nouvelle catégorie, tout en créant un nouveau produit

        if (isset($_FILES['image_utilisateur']) and $_FILES['image_utilisateur']['error'] == 0) {
            $fichier_image = basename($_FILES['image_utilisateur']['name']);
            move_uploaded_file($_FILES['image_utilisateur']['tmp_name'], '/image/' . $fichier_image);
        }

        $idCategorie = Modele_Catalogue::Categorie_Creer(
            $_REQUEST["CategorieAvecProduit"],
            $_REQUEST["DescriptionCategorieAvecProduit"],
            0
        );

        $idProduit = Modele_Catalogue::Produit_Creer(
            $_REQUEST["nom"],
            $_REQUEST["description"],
            $_REQUEST["resume"],
            $fichier_image,
            $_REQUEST["prixCatalogueHT"],
            $idCategorie,
            $_REQUEST["idTVA"],
            $_REQUEST["DesactiverProduit"]
        );
        $produit = Modele_Catalogue::Produit_Select_ParId($idProduit);
        Modele_Catalogue::Produit_Update_Ref($produit["libelle"], $produit["nom"], $idProduit);
        // Une fois le produit crée, on lui affiche une page pour savoir si le produit a bien été crée ou non, ainsi qu'un lien pour revenir sur le catalogue
        $this->vue->addToCorps(new Vue_Produit_Creation($idProduit, false, true));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function nouveauProduit(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idCategorie = (int)($args['idCategorie'] ?? -1);
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $listeTVA = Modele_Catalogue::TVA_Select_Tous();
        $fichier_image = "";
        // Si l'utilisateur veut créer une nouvelle catégorie, tout en créant un nouveau produit

        if ($idCategorie!=-1) {
            $this->vue->addToCorps(new Vue_Catalogue_Formulaire(
                $listeCategorie,
                $listeTVA,
                true,
                false,
                "",
                "",
                "",
                "",
                "",
                "",
                $idCategorie
            ));
        } else
            $this->vue->addToCorps(new Vue_Catalogue_Formulaire($listeCategorie, $listeTVA, true, ));

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function mettreAJourProduit(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idProduit = $_REQUEST["idProduit"];
      
        $produit = Modele_Catalogue::Produit_Select_ParId($idProduit);
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $listeTVA = Modele_Catalogue::TVA_Select_Tous();

        if (isset($_FILES['image_utilisateur']) and $_FILES['image_utilisateur']['error'] == 0) {
            $fichier_image = basename($_FILES['image_utilisateur']['name']);
            move_uploaded_file($_FILES['image_utilisateur']['tmp_name'], '/image/' . $fichier_image);
        } else {
            $fichier_image = $_REQUEST["fichierImage"];
        }
        Modele_Catalogue::Produit_Modifier(
            $idProduit,
            $_REQUEST["nom"],
            $_REQUEST["description"],
            $_REQUEST["resume"],
            $fichier_image,
            $_REQUEST["prixCatalogueHT"],
            $_REQUEST["idCategorie"],
            $_REQUEST["idTVA"],
            $_REQUEST["DesactiverProduit"]
        );
        // Une fois le produit modifié, on réaffiche tout le catalogue
        $listeProduit = Modele_Catalogue::Produit_Select();
        $this->vue->addToCorps(new Vue_Produit_Tous($listeProduit));


        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ModifierProduit(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $idProduit = (int)($args['id'] ?? 0);
        $produit = Modele_Catalogue::Produit_Select_ParId($idProduit);
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $listeTVA = Modele_Catalogue::TVA_Select_Tous();

        $this->vue->addToCorps(new Vue_Catalogue_Formulaire(
            $listeCategorie,
            $listeTVA,
            false,
            false,
            $produit["idProduit"],
            $produit["nom"],
            $produit["description"],
            $produit["resume"],
            $produit["fichierImage"],
            $produit["prixCatalogueHT"],
            $produit["idCategorie"],
            $produit["idTVA"],
            $produit["desactiverProduit"]
        ));

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function AjouterCategorie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $this->vue->addToCorps(new Vue_Liste_Categorie($listeCategorie));

        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function DesactiverCategorie(Request $request, Response $response, array $args): Response
    {
         
        return $this->ActiverCategorie($request, $response, $args);
    }

    public function ActiverCategorie(Request $request, Response $response, array $args): Response
    {
        
        $idCategorie = (int)($args['idCategorie'] ?? 0);
        // si l'utilisateur clique sur Désactiver/activer
        // On modifie la valeur dans la BDD
        // On affiche soit le bouton Activer soit le bouton Désactiver en fonction
       
        $categorie = Modele_Catalogue::Categorie_Select_ParID($idCategorie);
        switch ($categorie["desactiverCategorie"]) {
            case 0:
                 $this->init();
                $this->vue->addToCorps(new Vue_Demande_Approbation_Desactivation($idCategorie, $categorie["libelle"]));
                //$response->getBody()->write($this->vue->donneStr());
                break;
            case 1:
                $categorie["desactiverCategorie"] = 0;
                Modele_Catalogue::Categorie_Modifier_Desactivation($idCategorie, $categorie["desactiverCategorie"]);
                //$response->getBody()->write($this->vue->donneStr());
                 $this->init();
                break;
        }
        
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $this->vue->addToCorps(new Vue_Liste_Categorie($listeCategorie));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function OuiDesactivation(Request $request, Response $response, array $args): Response
    {
        
        $idCategorie = (int)($args['idCategorie'] ?? -1);
        $categorie = Modele_Catalogue::Categorie_Select_ParID($idCategorie);
        $categorie["desactiverCategorie"] = 1;
        Modele_Catalogue::Categorie_Modifier_Desactivation($idCategorie, $categorie["desactiverCategorie"]);
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $this->init();
        $this->vue->addToCorps(new Vue_Liste_Categorie($listeCategorie));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function ModifierCategorie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        // l'utilisateur clique sur "Modifier", on lui affiche le formulaire de modification
        $idCategorie = (int)($args['idCategorie'] ?? 0);
        $categorie = Modele_Catalogue::Categorie_Select_ParID($idCategorie);
        $this->vue->addToCorps(new Vue_Categorie_Creation_Modification_(
            false,
            $categorie["idCategorie"],
            $categorie["libelle"],
            $categorie["description"]
        ));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function mettreAJourCategorie(Request $request, Response $response, array $args): Response
    {
        
        // l'utilsateur clique sur mettre à jour, pour valider sa modification
        $idCategorie = (int)($args['idCategorie'] ?? 0);
        Modele_Catalogue::Categorie_Modifier($idCategorie, $_REQUEST["libelle"], $_REQUEST["description"]);
        $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
        $this->init();
        $this->vue->addToCorps(new Vue_Liste_Categorie($listeCategorie));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function nouvelleCategorie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        // l'utilisateur veut ajouter une nouvelle catégorie, on lui affiche le formulaire de création
        $this->vue->addToCorps(new Vue_Categorie_Creation_Modification_(true, false));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function CreerCategorie(Request $request, Response $response, array $args): Response
    {
        $this->init();
        // L'utlisateur a cliquer sur Créer, afin d'ajouter sa nouvelle catégorie
        $categorie = Modele_Catalogue::Categorie_Select_Par_Libelle($_REQUEST["libelle"]);
        if (is_array($categorie)) {
            $this->vue->addToCorps(new Vue_AfficherMessage("<h3>Cette catégorie existe déjà, veuillez recommencer</h3>"));

            $this->vue->addToCorps(new Vue_Categorie_Creation_Modification_(true, false));
        } else {
            $desactiver = 0;
            $reponse = Modele_Catalogue::Categorie_Creer($_REQUEST["libelle"], $_REQUEST["description"], $desactiver);
            // Une fois la catégorie crée, on lui affiche une page pour savoir si la catégorie a bien été crée ou non, ainsi qu'un lien pour revenir sur le catalogue
            $this->vue->addToCorps(new Vue_Produit_Creation($reponse, true, false));
            $listeCategorie = Modele_Catalogue::Categorie_Select_Tous();
            $listeTVA = Modele_Catalogue::TVA_Select_Tous();
            $this->vue->addToCorps(new Vue_Catalogue_Formulaire(
                $listeCategorie,
                $listeTVA,
                true,
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                $reponse
            ));
        }
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function okRechercher(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $produits_recherche = Modele_Catalogue::Produit_Rechercher($_REQUEST["recherche"]);
        $this->vue->addToCorps(new Vue_Produit_Tous($produits_recherche));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }

    public function default(Request $request, Response $response, array $args): Response
    {
        $this->init();
        $listeProduit = Modele_Catalogue::Produits_Select_Libelle_Categ();
        $this->vue->addToCorps(new Vue_Produit_Tous($listeProduit));
        $response->getBody()->write($this->vue->donneStr());
        return $response;
    }
}
