<?php

namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_Commande
{
    static function Commande_Select_Toute()
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select commande.id, commande.dateCreation, sum(commande_avoir_produit.prixCommandeHT * commande_avoir_produit.quantite) as prixTotalHT, sum(commande_avoir_produit.prixCommandeHT * (1+commande_avoir_produit.tauxTVA) * commande_avoir_produit.quantite) as prixTotalTTC, sum(commande_avoir_produit.quantite) as nbProduit, etat_commande.libelle as libEtat, denomination
    from commande
        inner join commande_avoir_produit
            on commande.id = commande_avoir_produit.idCommande
        inner join produit p 
            on commande_avoir_produit.idProduit = p.idProduit 
        inner join etat_commande
            on idEtatCommande = commande.etat
       inner join entreprise
            on entreprise.idEntreprise = commande.idEntreprise
    group by commande.id, commande.dateCreation, etat_commande.libelle');
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        //  var_dump($tableauReponse);
        //  var_dump($idEntreprise);
        return $tableauReponse;
    }

    static function Commande_Select_Par_Etat($idEtatCommande)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select commande.id, commande.dateCreation, sum(commande_avoir_produit.prixCommandeHT * commande_avoir_produit.quantite) as prixTotalHT, sum(commande_avoir_produit.prixCommandeHT * (1+commande_avoir_produit.tauxTVA) * commande_avoir_produit.quantite) as prixTotalTTC, sum(commande_avoir_produit.quantite) as nbProduit, etat_commande.libelle as libEtat, denomination
 , commande.idEntreprise   from commande
        inner join commande_avoir_produit
            on commande.id = commande_avoir_produit.idCommande
        inner join produit p 
            on commande_avoir_produit.idProduit = p.idProduit 
        inner join etat_commande
            on idEtatCommande = commande.etat
        inner join entreprise
            on entreprise.idEntreprise = commande.idEntreprise
    
        where  idEtatCommande = :idEtatCommande
    group by commande.id, commande.dateCreation, etat_commande.libelle');
        $requetePreparee->bindValue('idEtatCommande', $idEtatCommande);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        //  var_dump($tableauReponse);
        //  var_dump($idEntreprise);
        return $tableauReponse;
    }

    static function Commande_Select_ParIdEntreprise($idEntreprise)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select commande.id, commande.dateCreation, sum(commande_avoir_produit.prixCommandeHT * commande_avoir_produit.quantite) as prixTotalHT, sum(commande_avoir_produit.prixCommandeHT * (1+commande_avoir_produit.tauxTVA) * commande_avoir_produit.quantite) as prixTotalTTC, sum(commande_avoir_produit.quantite) as nbProduit, etat_commande.libelle as libEtat
    from commande
        inner join commande_avoir_produit
            on commande.id = commande_avoir_produit.idCommande
        inner join produit p 
            on commande_avoir_produit.idProduit = p.idProduit
        inner join historique_etat_commande hec 
            on commande.etat = hec.etat and commande_avoir_produit.idCommande = commande.id
        inner join etat_commande
            on idEtatCommande = commande.etat
        where idEntreprise = :idEntreprise
    and commande.etat != 1
    group by commande.id, commande.dateCreation, etat_commande.libelle');
        $requetePreparee->bindValue('idEntreprise', $idEntreprise);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        //  var_dump($tableauReponse);
        //  var_dump($idEntreprise);
        return $tableauReponse;
    }

    static function Caddie_Select_ParIdEntreprise($idEntreprise)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select commande.*
    from commande
    where idEntreprise = :idEntreprise
    and etat= 1');
        $requetePreparee->bindValue('idEntreprise', $idEntreprise);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        if (count($tableauReponse) == 1)
            return $tableauReponse[0];
        return false;
    }

    static function Historique_Etat_Commande_Select_ParIdCommande($idCommande)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select EC.*, HEC.*, utilisateur.login, salarie.nom, salarie.prenom
    from historique_etat_commande HEC
        inner join etat_commande EC on HEC.etat = EC.idEtatCommande
        left join salarie on HEC.idSalarie = salarie.idSalarie
        left join utilisateur on HEC.idUtilisateur = utilisateur.idUtilisateur
    where HEC.idCommande = :idCommande
order by HEC.dateHeure desc
    
    ');
        $requetePreparee->bindValue('idCommande', $idCommande);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        /*   var_dump($idProduit, $idCommande);
           var_dump($tableauReponse);*/
        if (count($tableauReponse) >= 1)
            return $tableauReponse;
        return false;
    }

    static function Commande_Select_ParIdCommande($idCommande)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select commande.*
    from commande
    where id = :idCommande
    
    ');
        $requetePreparee->bindValue('idCommande', $idCommande);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        /*   var_dump($idProduit, $idCommande);
           var_dump($tableauReponse);*/
        if (count($tableauReponse) >= 1)
            return $tableauReponse[0];
        return false;
    }

    static function Commande_Avoir_Article_Select_ParIdCommande($idCommande)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select commande_avoir_produit.*, produit.*, categorie.libelle as libelleCat, tva.*
    from commande_avoir_produit  
        inner join produit
            on commande_avoir_produit.idProduit  = produit.idProduit
        inner join categorie
            on produit.idCategorie  = categorie.idCategorie 
        inner join tva
            on tva.idTVA = produit.idTVA
    where commande_avoir_produit.idCommande = :idCommande
    
    ');
        $requetePreparee->bindValue('idCommande', $idCommande);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        /*   var_dump($idProduit, $idCommande);
           var_dump($tableauReponse);*/
        if (count($tableauReponse) >= 1)
            return $tableauReponse;
        return false;
    }

    static function Article_Select_PourCaddie($idCommande, $idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select commande_avoir_produit.*
    from commande_avoir_produit  
    where commande_avoir_produit.idCommande = :idCommande
    and commande_avoir_produit.idProduit = :idProduit
    ');
        $requetePreparee->bindValue('idCommande', $idCommande);
        $requetePreparee->bindValue('idProduit', $idProduit);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        /*   var_dump($idProduit, $idCommande);
           var_dump($tableauReponse);*/
        if (count($tableauReponse) >= 1)
            return $tableauReponse[0];
        return false;
    }

    static function CommandeAvoirArticle_SupprimerArticle($idCommande, $idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'delete commande_avoir_produit.* from `commande_avoir_produit`
         where `idCommande` = :idCommande
         and  `idProduit` = :idProduit');
        $requetePreparee->bindParam('idCommande', $idCommande);
        $requetePreparee->bindParam('idProduit', $idProduit);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        //echo $reponse;
    }

    static function CommandeAvoirArticle_ChangerQuantite($idCommande, $idProduit, $quantite)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `commande_avoir_produit`
         set quantite = :quantite
         where `idCommande` = :idCommande
         and  `idProduit` = :idProduit');
        $requetePreparee->bindParam('idCommande', $idCommande);
        $requetePreparee->bindParam('idProduit', $idProduit);
        $requetePreparee->bindParam('quantite', $quantite);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        //echo $reponse;
    }

    static function CommandeAvoirArticle_Ajouter_Article($idCommande, $idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $produit = Modele_Catalogue::Produit_Select_ParId($idProduit);

        $requetePreparee = $connexionPDO->prepare(
            'INSERT INTO `commande_avoir_produit` (`idCommande`, `idProduit`, `quantite`, `prixCommandeHT`, `tauxTVA`) 
         VALUES ( :idCommande, :idProduit, 1, :prixCatalogueHT, :tauxTVA);');
        $requetePreparee->bindParam('idCommande', $idCommande);
        $requetePreparee->bindParam('idProduit', $idProduit);
        $requetePreparee->bindParam('prixCatalogueHT', $produit["prixCatalogueHT"]);
        $requetePreparee->bindParam('tauxTVA', $produit["tauxTVA"]);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
    }

    static function Panier_Ajouter_Produit_ParIdProduit($idEntreprise, $idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        //On recherche si l'entreprise a un panier existant
        $panier = self::Caddie_Select_ParIdEntreprise($idEntreprise);

        if ($panier == false) {
            // On crée le panier
            $date = date("Y-m-d H:i:s");
            $requetePreparee = $connexionPDO->prepare(
                'INSERT INTO commande ( dateCreation, idEntreprise, etat) 
         VALUES ( :date, :idEntreprise, 1);');


            $requetePreparee->bindParam(':date', $date);
            $requetePreparee->bindParam(':idEntreprise', $idEntreprise);

            $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
            //echo $reponse;
            $idPanier = $connexionPDO->lastInsertId();
        } else {
            $idPanier = $panier["id"];
        }
        //on recherche si l'article est déjà dans le panier
        $article = self::Article_Select_PourCaddie($idPanier, $idProduit);
        if ($article == false) {
            // On ajoute l'article au panier
            self::CommandeAvoirArticle_Ajouter_Article($idPanier, $idProduit);
        } else {

            self::CommandeAvoirArticle_ChangerQuantite($idPanier, $idProduit, $article["quantite"] + 1);

        }

    }


    static function Panier_DiminuerQTT_Article($idEntreprise, $idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $panier = self::Caddie_Select_ParIdEntreprise($idEntreprise);

        if($panier != false) {
            $idPanier = $panier["id"];

            //on recherche si l'article est déjà dans le panier
            $article = self::Article_Select_PourCaddie($idPanier, $idProduit);
            if ($article != false) {
                if ($article["quantite"] - 1 <= 0) {
                    self::CommandeAvoirArticle_SupprimerArticle($idPanier, $idProduit);
                } else {
                    self::CommandeAvoirArticle_ChangerQuantite($idPanier, $idProduit, $article["quantite"] - 1);
                }
            }
        }

    }

    static function Panier_AugmenterQTT_Article($idEntreprise, $idProduit)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $panier = self::Caddie_Select_ParIdEntreprise($idEntreprise);

        if($panier != false) {
            $idPanier = $panier["id"];

            //on recherche si l'article est déjà dans le panier
            $article = self::Article_Select_PourCaddie($idPanier, $idProduit);

            self::CommandeAvoirArticle_ChangerQuantite($idPanier, $idProduit, $article["quantite"] + 1);
        }
    }

    static function Panier_ListeArticle($idEntreprise)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $panier = self::Caddie_Select_ParIdEntreprise($idEntreprise);
        if ($panier == false) {
            return [];
        } else {
            $listeProduits = self::Commande_Avoir_Article_Select_ParIdCommande($panier["id"]);

            return $listeProduits;
        }
    }


    static function Panier_Quantite($idEntreprise)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $panier = self::Caddie_Select_ParIdEntreprise($idEntreprise);
        if ($panier == false) {
            return 0;
        } else {
            $listeProduits = self::Commande_Avoir_Article_Select_ParIdCommande($panier["id"]);

            $cnt = 0;
            foreach ($listeProduits as $produit) {
                $cnt += $produit["quantite"];
            }
            return $cnt;
        }
    }

    static function HistoriqueEtatCommande_Inserer($idCommande, $etat, $infoComplementaire = "", $idSalarie = -1, $idUtilisateur = -1)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        self::Commande_Update_Etat($idCommande, $etat);

        $requetePreparee = $connexionPDO->prepare(
            'insert into `historique_etat_commande` (idCommande, etat, dateHeure, infoComplementaire, idSalarie, idUtilisateur)  
        values (:idCommande, :etat, :dateHeure, :infoComplementaire, :idSalarie, :idUtilisateur) ');
        $requetePreparee->bindParam('idCommande', $idCommande);
        $date = date("Y-m-d H:i:s");
        $requetePreparee->bindParam('etat', $etat);
        $requetePreparee->bindParam('dateHeure', $date);
        $requetePreparee->bindParam('infoComplementaire', $infoComplementaire);
        $requetePreparee->bindParam('idSalarie', $idSalarie);
        $requetePreparee->bindParam('idUtilisateur', $idUtilisateur);


        $reponse = $requetePreparee->execute();
    }


    static function Commande_Update_Etat($idCommande, $etat)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `commande`
         set etat = :etat
         where `id` = :idCommande 
         ');
        $requetePreparee->bindParam('idCommande', $idCommande);
        $requetePreparee->bindParam('etat', $etat);

        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
    }

    static function Commande_Valider_Caddie($idCommande, $idSalarie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        //  Commande_Update_Etat( $idCommande, 2);
        $salarie = Modele_Salarie::Salarie_Select_byId($idSalarie);
        self::HistoriqueEtatCommande_Inserer($idCommande, 2, "Commande passée par $salarie[nom] $salarie[prenom]",
            $idSalarie);
        //echo $reponse;
    }


    static function EtatCommande_Liste()
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
    select etat_commande.*
    from etat_commande  
    ');
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        /*   var_dump($idProduit, $idCommande);
           var_dump($tableauReponse);*/
        if (count($tableauReponse) >= 1)
            return $tableauReponse;
        return false;
    }
}