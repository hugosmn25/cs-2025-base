<?php

namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;
use Throwable;

class Modele_FacteurAuthentification
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function Facteur_SelectTout(): array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
            SELECT id, libelle
            FROM FacteurAuthentification
            ORDER BY libelle');
        $requetePreparee->execute();

        return $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function Facteur_SelectParId(int $idFacteur): ?array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
            SELECT id, libelle
            FROM FacteurAuthentification
            WHERE id = :idFacteur');
        $requetePreparee->bindParam('idFacteur', $idFacteur, PDO::PARAM_INT);
        $requetePreparee->execute();

        $facteur = $requetePreparee->fetch(PDO::FETCH_ASSOC);

        return $facteur !== false ? $facteur : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function Avoir2FA_SelectParUtilisateur(int $idUtilisateur): ?array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
            SELECT idFacteurAuthentification, valeur
            FROM Avoir2FA
            WHERE idUtilisateur = :idUtilisateur
            LIMIT 1');
        $requetePreparee->bindParam('idUtilisateur', $idUtilisateur, PDO::PARAM_INT);
        $requetePreparee->execute();

        $enregistrement = $requetePreparee->fetch(PDO::FETCH_ASSOC);

        return $enregistrement !== false ? $enregistrement : null;
    }

    public static function Avoir2FA_DefinirPourUtilisateur(int $idUtilisateur, int $idFacteur, string $valeur = ''): bool
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();

        $connexionPDO->beginTransaction();

        try {
            $requeteSuppression = $connexionPDO->prepare('
                DELETE FROM Avoir2FA
                WHERE idUtilisateur = :idUtilisateur');
            $requeteSuppression->bindParam('idUtilisateur', $idUtilisateur, PDO::PARAM_INT);
            $requeteSuppression->execute();

            $requeteInsertion = $connexionPDO->prepare('
                INSERT INTO Avoir2FA (idUtilisateur, idFacteurAuthentification, valeur)
                VALUES (:idUtilisateur, :idFacteur, :valeur)');
            $requeteInsertion->bindParam('idUtilisateur', $idUtilisateur, PDO::PARAM_INT);
            $requeteInsertion->bindParam('idFacteur', $idFacteur, PDO::PARAM_INT);
            $requeteInsertion->bindParam('valeur', $valeur, PDO::PARAM_STR);
            $requeteInsertion->execute();

            $connexionPDO->commit();
            return true;
        } catch (Throwable $throwable) {
            $connexionPDO->rollBack();
            return false;
        }
    }
}
