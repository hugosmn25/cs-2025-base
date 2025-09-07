<?php

namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_Token
{

    static function Salarie_CreerToken( $codeAction, $idUtilisateur, $dateFin):string
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