<?php

namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_Token
{

    static function Salarie_CreerToken( $codeAction, $idUtilisateur, $dateFin): string|false
    {
        return self::CreerToken((int) $codeAction, (int) $idUtilisateur, $dateFin);
    }

    public static function CreerToken(int $codeAction, int $idUtilisateur, \DateTimeInterface $dateFin): string|false
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $octetsAleatoires = openssl_random_pseudo_bytes (256) ;

        $valeur = sodium_bin2base64($octetsAleatoires, SODIUM_BASE64_VARIANT_ORIGINAL);

        $requetePreparee = $connexionPDO->prepare(
            'INSERT INTO `token` 
            (valeur, codeAction, idUtilisateur, dateFin)
            VALUES (:paramvaleur, :paramcodeAction, :paramidUtilisateur, :paramdateFin)');
        $requetePreparee->bindParam('paramcodeAction', $codeAction);
        $requetePreparee->bindParam('paramvaleur', $valeur);
        $requetePreparee->bindParam('paramidUtilisateur', $idUtilisateur);
        $dateFinString = $dateFin->format('Y-m-d H:i:s');
        $requetePreparee->bindParam('paramdateFin', $dateFinString);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        if($reponse)
            return $valeur;
        else
            return false;
    }

    public static function Token_SelectActifParUtilisateur(int $idUtilisateur, int $codeAction): ?array
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('SELECT * FROM `token` WHERE idUtilisateur = :paramidUtilisateur AND codeAction = :paramcodeAction AND dateFin > NOW() ORDER BY dateFin DESC LIMIT 1');
        $requetePreparee->bindParam('paramidUtilisateur', $idUtilisateur, PDO::PARAM_INT);
        $requetePreparee->bindParam('paramcodeAction', $codeAction, PDO::PARAM_INT);
        $reponse = $requetePreparee->execute();
        if ($reponse === false) {
            return null;
        }
        $token = $requetePreparee->fetch(PDO::FETCH_ASSOC);
        return $token ?: null;
    }

    public static function Token_SupprimerParUtilisateur(int $idUtilisateur, int $codeAction): bool
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('DELETE FROM `token` WHERE idUtilisateur = :paramidUtilisateur AND codeAction = :paramcodeAction');
        $requetePreparee->bindParam('paramidUtilisateur', $idUtilisateur, PDO::PARAM_INT);
        $requetePreparee->bindParam('paramcodeAction', $codeAction, PDO::PARAM_INT);
        return (bool) $requetePreparee->execute();
    }

    public static function Token_SupprimerParValeur(string $token): bool
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('DELETE FROM `token` WHERE valeur = :paramtoken');
        $requetePreparee->bindParam('paramtoken', $token);
        return (bool) $requetePreparee->execute();
    }

    public static function Token_Select(mixed $token)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('SELECT * FROM `token` WHERE valeur = :paramtoken');
        $requetePreparee->bindParam('paramtoken', $token);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        //error_log("Token_Select : " . $token);
        //}
        return $requetePreparee->fetch(PDO::FETCH_ASSOC);
    }
}
