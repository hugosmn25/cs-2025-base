<?php

namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_Catalogue
{
    /**
     * @param $connexionPDO : connexion à la base de données
     * @return mixed : le tableau des Produit ou null (something went wrong...)
     */
static function  Produit_Select()
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
select produit.*, libelle, tva.tauxTVA
    from `produit`, `categorie`, `tva`
    where categorie.idCategorie = produit.idCategorie
    and produit.idTVA = tva.idTVA
    order by idProduit');
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        return $tableauReponse;
    }

    static function Produits_Select_Libelle_Categ($type = "")
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('select produit.*, libelle 
from `produit`, `categorie`
where categorie.idCategorie = produit.idCategorie ' . ($type == "client" ? "AND desactiverProduit = 0" : "") . ' 
order by idProduit');
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        return $tableauReponse;
    }

    static function Categorie_Select_Tous()
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('select categorie.*
from `categorie`
order by idCategorie');
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        return $tableauReponse;
    }

// pour sélectionner la liste de produit selon l'Id Categorie du bouton cliqué
    static function Select_Produit_Select_ParIdCateg($idCategorie, $type = "")
    {

        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
select produit.*, categorie.libelle
from `categorie` 
    inner join `produit` 
            on categorie.idCategorie = produit.idCategorie
where  categorie.idCategorie = :paramId ' . ($type == "client" ? "AND desactiverProduit = 0" : ""));
        $requetePreparee->bindParam('paramId', $idCategorie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        return $tableauReponse;
    }

    /**
     * @param $idProduit
     * @return mixed
     */
    static function Produit_Select_ParId($idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('select produit.*, libelle, tva.tauxTVA
    from `produit`, `categorie`, `tva`
    where categorie.idCategorie = produit.idCategorie
    and produit.idTVA = tva.idTVA
    and idProduit = :paramId');
        $requetePreparee->bindParam('paramId', $idProduit);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $Produit = $requetePreparee->fetch(PDO::FETCH_ASSOC);
        return $Produit;
    }

    static function Categorie_Select_ParID($idCategorie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('select * from `categorie` where idCategorie = :paramId');
        $requetePreparee->bindParam('paramId', $idCategorie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $Categorie = $requetePreparee->fetch(PDO::FETCH_ASSOC);
        return $Categorie;
    }

    static function Categorie_Select_Par_Libelle( $libelle)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('select * from `categorie` where libelle = :paramlibelle');
        $requetePreparee->bindParam('paramlibelle', $libelle);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $Categorie = $requetePreparee->fetch(PDO::FETCH_ASSOC);
        return $Categorie;
    }

//fonction qui va récupérer les pourcentages dans la table TVA
    static function TVA_Select_Tous()
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('select *
from `tva`  ');
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        return $tableauReponse;
    }

    /**
     * @param $connexionPDO
     * @param $idProduit
     * @return mixed
     */
    static function Produit_Select_Par_Categorie($idCategorie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('select COUNT(*) as nb_produit from `produit` where idCategorie = :paramidCategorie');
        $requetePreparee->bindParam('paramidCategorie', $idCategorie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $Produit = $requetePreparee->fetch(PDO::FETCH_ASSOC);
        return $Produit;
    }

    /**
     * @param $connexionPDO
     * @param $login
     * @param $niveauAutorisation
     * @return mixed
     */
    static function Produit_Creer($nom, $description, $resume, $fichierImage, $prixCatalogueHT, $idCategorie, $idTVA, $desactiver)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'INSERT INTO `produit` (`idProduit`, `nom`, `description`, `resume`, `fichierImage`, `prixCatalogueHT`, `idCategorie`, `idTVA`, `desactiverProduit`) 
         VALUES (NULL, :paramnom, :paramdescription, :paramresume, :paramfichierImage, :paramprixVenteHT, :paramidCategorie, :paramidTVA, :paramdesactiverProduit);');

        $requetePreparee->bindParam('paramnom', $nom);
        $requetePreparee->bindParam('paramdescription', $description);
        $requetePreparee->bindParam('paramresume', $resume);
        $requetePreparee->bindParam('paramfichierImage', $fichierImage);
        $requetePreparee->bindParam('paramprixVenteHT', $prixCatalogueHT);
        $requetePreparee->bindParam('paramidCategorie', $idCategorie);
        $requetePreparee->bindParam('paramidTVA', $idTVA);
        $requetePreparee->bindParam('paramdesactiverProduit', $desactiver);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        //echo $reponse;
        $idProduit = $connexionPDO->lastInsertId();
        return $idProduit;
    }

    static function Produit_Update_Ref($nom_categorie, $nom_produit, $idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $reference = strtoupper(substr($nom_categorie, 0, 3)) . "_" . strtoupper(substr($nom_produit, 0, 3)) . "_" . $idProduit;
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `produit` 
        SET `reference`= :paramref
        WHERE idProduit = :paramidProduit');
        $requetePreparee->bindParam('paramref', $reference);
        $requetePreparee->bindParam('paramidProduit', $idProduit);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        return $reponse;
    }

    /**
     * @param $connexionPDO
     * @param $idUtilisateur
     * @return mixed
     */
    static function Produit_Supprimer($idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('delete produit.* from `produit` where idProduit = :paramId');
        $requetePreparee->bindParam('paramId', $idProduit);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        return $reponse;
    }

    /**
     * @param $connexionPDO
     * @param $idUtilisateur
     * @param $login
     * @param $niveauAutorisation
     * @return mixed
     */
// valeur du champ desactiverPrduit
// valeur 0 : c'est actif sur le catalogue
// valeur 1 : c'est désactivé sur le catalogue
    static function Produit_Modifier($idProduit, $nom, $description, $resume, $fichierImage, $prixCatalogueHT, $idCategorie, $idTVA, $desactiverProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `produit` 
SET `nom`= :paramnom,
`description`= :paramdescription,
`resume`= :paramresume,
`fichierImage`= :paramfichierImage,
`prixCatalogueHT`= :paramprixVenteHT,
`idCategorie`= :paramidCategorie,
`idTVA`= :paramidTVA,
`desactiverProduit`= :paramdesactiverProduit
WHERE idProduit = :paramidProduit');
        $requetePreparee->bindParam('paramnom', $nom);
        $requetePreparee->bindParam('paramdescription', $description);
        $requetePreparee->bindParam('paramresume', $resume);
        $requetePreparee->bindParam('paramfichierImage', $fichierImage);
        $requetePreparee->bindParam('paramprixVenteHT', $prixCatalogueHT);
        $requetePreparee->bindParam('paramidCategorie', $idCategorie);
        $requetePreparee->bindParam('paramidTVA', $idTVA);
        $requetePreparee->bindParam('paramdesactiverProduit', $desactiverProduit);
        $requetePreparee->bindParam('paramidProduit', $idProduit);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        return $reponse;
    }


// fonction pour modifier la valeur du champ desactiverPrduit
// valeur 0 : c'est actif sur le catalogue
// valeur 1 : c'est désactivé sur le catalogue
    static function Categorie_Modifier_Desactivation($idCategorie, $desactiverCategorie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `categorie` 
SET `desactiverCategorie`= :paramdesactiverCategorie
WHERE idCategorie = :paramidCategorie');
        $requetePreparee->bindParam('paramdesactiverCategorie', $desactiverCategorie);
        $requetePreparee->bindParam('paramidCategorie', $idCategorie);
        $reponse1 = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `produit` 
SET `desactiverProduit`= :paramdesactiverCategorie
WHERE idCategorie = :paramidCategorie');
        $requetePreparee->bindParam('paramdesactiverCategorie', $desactiverCategorie);
        $requetePreparee->bindParam('paramidCategorie', $idCategorie);
        $reponse2 = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        if ($reponse1 and $reponse2) {
            return true;
        } else {
            return false;
        }
    }

//Modifier une catégorie
    static function Categorie_Modifier($idCategorie, $libelle, $description)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `categorie` 
SET `libelle`= :paramlibelle,
`description`= :paramdescription
WHERE idCategorie = :paramidCategorie');
        $requetePreparee->bindParam('paramlibelle', $libelle);
        $requetePreparee->bindParam('paramdescription', $description);
        $requetePreparee->bindParam('paramidCategorie', $idCategorie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        return $reponse;
    }

    static function Categorie_Creer($libelle, $description, $desactiver)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'INSERT INTO `categorie` (`idCategorie`, `libelle`, `description`, `desactiverCategorie`) 
         VALUES (NULL, :paramlibelle, :paramdescription, :paramdesactiverCategorie);');
        $requetePreparee->bindParam('paramlibelle', $libelle);
        $requetePreparee->bindParam('paramdescription', $description);
        $requetePreparee->bindParam('paramdesactiverCategorie', $desactiver);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        //echo $reponse;
        $idCategorie = $connexionPDO->lastInsertId();
        return $idCategorie;
    }

    static function Produit_Rechercher($recherche, $type = "")
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select produit.*, libelle
    from `produit`, `categorie`
    where categorie.idCategorie = produit.idCategorie
    and (produit.nom LIKE :paramrecherche
    or categorie.libelle LIKE :paramrecherche)  ' . ($type == "client" ? " AND desactiverProduit = 0 " : "") . '  
     order by idProduit');
        $requetePreparee->bindValue('paramrecherche', "%" . $recherche . "%");
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        return $tableauReponse;
    }
}