<?php
namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_VersionsPolitique
{
    /**
     * Ajoute une version de politique
     */
    static function VersionsPolitique_Ajouter(string $codeVersion, string $contenu, string $hashContenu, string $publieLe, int $courante = 0)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requete = $connexionPDO->prepare('INSERT INTO `versions_politique` (code_version, contenu, hash_contenu, publie_le, courante) VALUES (:code_version, :contenu, :hash_contenu, :publie_le, :courante)');
        $requete->bindParam('code_version', $codeVersion);
        $requete->bindParam('contenu', $contenu);
        $requete->bindParam('hash_contenu', $hashContenu);
        $requete->bindParam('publie_le', $publieLe);
        $requete->bindParam('courante', $courante, PDO::PARAM_INT);
        $ok = $requete->execute();
        if ($ok !== false) {
            return (int)$connexionPDO->lastInsertId();
        }
        return false;
    }

    /**
     * Met à jour une version (par id)
     */
    static function VersionsPolitique_MAJ(int $id, string $codeVersion, string $contenu, string $hashContenu, string $publieLe, int $courante)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requete = $connexionPDO->prepare('UPDATE `versions_politique` SET code_version = :code_version, contenu = :contenu, hash_contenu = :hash_contenu, publie_le = :publie_le, courante = :courante WHERE id = :id');
        $requete->bindParam('code_version', $codeVersion);
        $requete->bindParam('contenu', $contenu);
        $requete->bindParam('hash_contenu', $hashContenu);
        $requete->bindParam('publie_le', $publieLe);
        $requete->bindParam('courante', $courante, PDO::PARAM_INT);
        $requete->bindParam('id', $id, PDO::PARAM_INT);
        return $requete->execute();
    }

    /**
     * Rend une version courante (met toutes les autres à 0)
     */
    static function VersionsPolitique_DefinirCourante(int $id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        try {
            $connexionPDO->beginTransaction();
            $connexionPDO->exec('UPDATE `versions_politique` SET courante = 0');
            $requete = $connexionPDO->prepare('UPDATE `versions_politique` SET courante = 1 WHERE id = :id');
            $requete->bindParam('id', $id, PDO::PARAM_INT);
            $requete->execute();
            $connexionPDO->commit();
            return true;
        } catch (\Throwable $e) {
            if ($connexionPDO->inTransaction()) {
                $connexionPDO->rollBack();
            }
            return false;
        }
    }

    /**
     * Retire le statut courante pour l'id donné (courante = 0)
     */
    static function VersionsPolitique_RetirerCourante(int $id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requete = $connexionPDO->prepare('UPDATE `versions_politique` SET courante = 0 WHERE id = :id');
        $requete->bindParam('id', $id, PDO::PARAM_INT);
        return $requete->execute();
    }

    /**
     * Sélectionne toutes les versions
     */
    static function VersionsPolitique_Select_All(): array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requete = $connexionPDO->prepare('SELECT * FROM `versions_politique` ORDER BY publie_le DESC, id DESC');
        $requete->execute();
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sélectionne la version courante (ou null si aucune)
     */
    static function VersionsPolitique_Select_Courante()
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requete = $connexionPDO->prepare('SELECT * FROM `versions_politique` WHERE courante = 1 ORDER BY publie_le DESC, id DESC');
        $requete->execute();
        $row = $requete->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }

    /**
     * Sélectionne par id
     */
    static function VersionsPolitique_Select_ById(int $id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requete = $connexionPDO->prepare('SELECT * FROM `versions_politique` WHERE id = :id');
        $requete->bindParam('id', $id, PDO::PARAM_INT);
        $requete->execute();
        $row = $requete->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }

    /**
     * Sélectionne par code_version
     */
    static function VersionsPolitique_Select_ByCode(string $codeVersion)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requete = $connexionPDO->prepare('SELECT * FROM `versions_politique` WHERE code_version = :code_version');
        $requete->bindParam('code_version', $codeVersion);
        $requete->execute();
        $row = $requete->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }
}
