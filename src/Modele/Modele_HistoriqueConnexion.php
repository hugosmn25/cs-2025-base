<?php

namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_HistoriqueConnexion
{

    public static function HistoriqueConnexion_Ajouter(int $idUtilisateur, ?string $ip = null, ?string $userAgent = null): bool
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $stmt = $connexionPDO->prepare('INSERT INTO `historique_connexion` (idUtilisateur, login, dateConnexion, ip, userAgent, reussite) VALUES (:uid, :login, NOW(), :ip, :ua, 1)');
        $stmt->bindParam('uid', $idUtilisateur, PDO::PARAM_INT);
        $stmt->bindValue('login', null, PDO::PARAM_NULL);
        $stmt->bindParam('ip', $ip);
        $stmt->bindParam('ua', $userAgent);
        return (bool)$stmt->execute();
    }

    public static function HistoriqueConnexion_EnregistrerTentative(string $login, bool $reussite, ?int $idUtilisateur = null, ?string $ip = null, ?string $userAgent = null): bool
    {
        try {
            $connexionPDO = Singleton_ConnexionPDO::getInstance();
            $stmt = $connexionPDO->prepare('INSERT INTO `historique_connexion` (idUtilisateur, login, dateConnexion, ip, userAgent, reussite) VALUES (:uid, :login, NOW(), :ip, :ua, :reussite)');
            if ($idUtilisateur === null) {
                $stmt->bindValue('uid', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue('uid', $idUtilisateur, PDO::PARAM_INT);
            }
            $stmt->bindParam('login', $login);
            $stmt->bindParam('ip', $ip);
            $stmt->bindParam('ua', $userAgent);
            $r = $reussite ? 1 : 0;
            $stmt->bindValue('reussite', $r, PDO::PARAM_INT);
            return (bool)$stmt->execute();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function HistoriqueConnexion_NombreEchecsRecents(string $login, int $seconds): int
    {
        try {
            $connexionPDO = Singleton_ConnexionPDO::getInstance();
            $cutoff = date('Y-m-d H:i:s', time() - $seconds);
            $stmt = $connexionPDO->prepare('SELECT COUNT(*) AS c FROM `historique_connexion` WHERE login = :login AND reussite = 0 AND dateConnexion > :cutoff');
            $stmt->bindParam('login', $login);
            $stmt->bindParam('cutoff', $cutoff);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function HistoriqueConnexion_DerniereEchec(string $login): ?string
    {
        try {
            $connexionPDO = Singleton_ConnexionPDO::getInstance();
            $stmt = $connexionPDO->prepare('SELECT MAX(dateConnexion) AS d FROM `historique_connexion` WHERE login = :login AND reussite = 0');
            $stmt->bindParam('login', $login);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['d'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function HistoriqueConnexion_Derniere(int $idUtilisateur): ?string
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $stmt = $connexionPDO->prepare('SELECT dateConnexion            
        FROM `historique_connexion`
        WHERE idUtilisateur = :uid 
        AND reussite = 1
        ORDER BY dateConnexion DESC LIMIT 1');
        $stmt->bindParam('uid', $idUtilisateur, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row["dateConnexion"] ?? null;
    }
}
