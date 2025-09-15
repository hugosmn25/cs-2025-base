<?php
namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_FinalitesConsentement
{
    /**
     * Crée une finalité de consentement
     * @param string $nom
     * @param int $actif 0/1 (par défaut 1)
     * @return mixed bool true/false selon l'exécution
     */
    static function FinalitesConsentement_Ajouter(string $nom, int $actif = 1)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'INSERT INTO `finalites_consentement` (nom, actif) VALUES (:nom, :actif)');
        $requetePreparee->bindParam('nom', $nom);
        $requetePreparee->bindParam('actif', $actif, PDO::PARAM_INT);
        $reponse = $requetePreparee->execute();
        return $reponse;
    }

    /**
     * Met à jour le nom d'une finalité
     * @param int $id
     * @param string $nom
     * @return mixed bool true/false selon l'exécution
     */
    static function FinalitesConsentement_MAJ(int $id, string $nom)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `finalites_consentement` SET nom = :nom WHERE id = :id');
        $requetePreparee->bindParam('nom', $nom);
        $requetePreparee->bindParam('id', $id, PDO::PARAM_INT);
        $reponse = $requetePreparee->execute();
        return $reponse;
    }

    /**
     * Active une finalité (actif = 1)
     * @param int $id
     * @return mixed bool true/false selon l'exécution
     */
    static function FinalitesConsentement_Activer(int $id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `finalites_consentement` SET actif = 1 WHERE id = :id');
        $requetePreparee->bindParam('id', $id, PDO::PARAM_INT);
        $reponse = $requetePreparee->execute();
        return $reponse;
    }

    /**
     * Désactive une finalité (actif = 0)
     * @param int $id
     * @return mixed bool true/false selon l'exécution
     */
    static function FinalitesConsentement_Desactiver(int $id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `finalites_consentement` SET actif = 0 WHERE id = :id');
        $requetePreparee->bindParam('id', $id, PDO::PARAM_INT);
        $reponse = $requetePreparee->execute();
        return $reponse;
    }

    /**
     * Retourne toutes les finalités
     * @return array liste associative des finalités
     */
    static function FinalitesConsentement_Select_All(): array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('SELECT * FROM `finalites_consentement` ORDER BY nom');
        $requetePreparee->execute();
        return $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retourne uniquement les finalités actives
     * @return array liste associative des finalités actives
     */
    static function FinalitesConsentement_Select_Actives(): array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('SELECT * FROM `finalites_consentement` WHERE actif = 1 ORDER BY nom');
        $requetePreparee->execute();
        return $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retourne une finalité par son id
     * @param int $id
     * @return array|false enregistrement ou false si introuvable
     */
    static function FinalitesConsentement_Select_ById(int $id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('SELECT * FROM `finalites_consentement` WHERE id = :id');
        $requetePreparee->bindParam('id', $id, PDO::PARAM_INT);
        $requetePreparee->execute();
        $row = $requetePreparee->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }

    /**
     * Retourne une finalité par son nom exact
     */
    static function FinalitesConsentement_Select_ByNom(string $nom)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('SELECT * FROM `finalites_consentement` WHERE nom = :nom LIMIT 1');
        $requetePreparee->bindParam('nom', $nom);
        $requetePreparee->execute();
        $row = $requetePreparee->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }
}
