<?php
namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_Consentements
{
    private static function ipToBinary(?string $ip): ?string
    {
        if ($ip === null || $ip === '') return null;
        $packed = @inet_pton($ip);
        return $packed !== false ? $packed : null;
    }

    /**
     * Ajoute un enregistrement de consentement
     */
    static function Consentements_Ajouter(?int $utilisateur_id, int $finalite_id, string $statut, int $version_politique_id, ?string $ip_adresse)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $sql = 'INSERT INTO `consentements` (utilisateur_id, finalite_id, statut, version_politique_id, ip_adresse) 
                VALUES (:utilisateur_id, :finalite_id, :statut, :version_politique_id, :ip_adresse)';
        $req = $connexionPDO->prepare($sql);
        if ($utilisateur_id === null) {
            $req->bindValue('utilisateur_id', null, PDO::PARAM_NULL);
        } else {
            $req->bindValue('utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        }
        $req->bindValue('finalite_id', $finalite_id, PDO::PARAM_INT);
        $req->bindValue('statut', $statut);
        $req->bindValue('version_politique_id', $version_politique_id, PDO::PARAM_INT);
        $ipBin = self::ipToBinary($ip_adresse);
        if ($ipBin === null) {
            $req->bindValue('ip_adresse', null, PDO::PARAM_NULL);
        } else {
            $req->bindValue('ip_adresse', $ipBin, PDO::PARAM_LOB);
        }
        $ok = $req->execute();
        if ($ok !== false) {
            return (int)$connexionPDO->lastInsertId();
        }
        return false;
    }

    /**
     * Met à jour un consentement (statut, version, ip)
     */
    static function Consentements_MAJ(int $id, string $statut, int $version_politique_id, ?string $ip_adresse)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $sql = 'UPDATE `consentements` 
                SET statut = :statut, version_politique_id = :version_politique_id, ip_adresse = :ip_adresse 
                WHERE id = :id';
        $req = $connexionPDO->prepare($sql);
        $req->bindValue('statut', $statut);
        $req->bindValue('version_politique_id', $version_politique_id, PDO::PARAM_INT);
        $ipBin = self::ipToBinary($ip_adresse);
        if ($ipBin === null) {
            $req->bindValue('ip_adresse', null, PDO::PARAM_NULL);
        } else {
            $req->bindValue('ip_adresse', $ipBin, PDO::PARAM_LOB);
        }
        $req->bindValue('id', $id, PDO::PARAM_INT);
        return $req->execute();
    }

    /**
     * Sélectionne toutes les lignes
     */
    static function Consentements_Select_All(): array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $req = $connexionPDO->prepare('SELECT * FROM `consentements` ORDER BY cree_le DESC, id DESC');
        $req->execute();
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sélection par id
     */
    static function Consentements_Select_ById(int $id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $req = $connexionPDO->prepare('SELECT * FROM `consentements` WHERE id = :id');
        $req->bindValue('id', $id, PDO::PARAM_INT);
        $req->execute();
        $row = $req->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }

    /**
     * Dernier consentement d'un utilisateur pour une finalité donnée
     */
    static function Consentements_Select_Dernier_ByUtilisateur_Finalite(int $utilisateur_id, int $finalite_id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $req = $connexionPDO->prepare('SELECT * FROM `consentements` 
                WHERE utilisateur_id = :utilisateur_id AND finalite_id = :finalite_id 
                ORDER BY cree_le DESC, id DESC LIMIT 1');
        $req->bindValue('utilisateur_id', $utilisateur_id, PDO::PARAM_INT);
        $req->bindValue('finalite_id', $finalite_id, PDO::PARAM_INT);
        $req->execute();
        $row = $req->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }

    /**
     * Dernier consentement par IP (utilisateur non connecté) pour une finalité donnée
     */
    static function Consentements_Select_Dernier_ByIp_Finalite(string $ip_adresse, int $finalite_id)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $ipBin = self::ipToBinary($ip_adresse);
        if ($ipBin === null) return false;
        $req = $connexionPDO->prepare('SELECT * FROM `consentements` 
                WHERE ip_adresse = :ip_adresse AND finalite_id = :finalite_id 
                ORDER BY cree_le DESC, id DESC LIMIT 1');
        $req->bindValue('ip_adresse', $ipBin, PDO::PARAM_LOB);
        $req->bindValue('finalite_id', $finalite_id, PDO::PARAM_INT);
        $req->execute();
        $row = $req->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }
}
