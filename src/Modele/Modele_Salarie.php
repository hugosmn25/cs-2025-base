<?php
namespace App\Modele;
use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_Salarie
{
    static function Salarie_Modifier_motDePasse_All( $motDePasseClair)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $parammotDePasseHache = password_hash($motDePasseClair, PASSWORD_DEFAULT);

        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `salarie` 
SET password = :parammotDePasseHache,
    actif = 1');
        $requetePreparee->bindParam('parammotDePasseHache', $parammotDePasseHache);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        return $reponse;
    }

    static function Salarie_Modifier_motDePasse($idSalarie, $motDePasseClair)
    {



        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $parammotDePasseHache = password_hash($motDePasseClair, PASSWORD_DEFAULT);

        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `salarie` 
SET password = :parammotDePasseHache
WHERE idSalarie = :paramidUtilisateur');
        $requetePreparee->bindParam('parammotDePasseHache', $parammotDePasseHache);
        $requetePreparee->bindParam('paramidUtilisateur', $idSalarie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        return $reponse;
    }

    static function Salarie_Select_Entreprise($idEntreprise)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
        select * 
        from `salarie` 
        where idEntreprise = :idEntreprise
         order by salarie.nom, salarie.prenom');
        $requetePreparee->bindParam('idEntreprise', $idEntreprise);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête

        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        return $tableauReponse;
    }

    static function Salarie_Select_byId($idSalarie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
        select * 
        from `salarie`
        where idSalarie = :idSalarie');
        $requetePreparee->bindParam('idSalarie', $idSalarie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête

        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        if (count($tableauReponse) > 0)
            return $tableauReponse[0];
        else
            return false;
    }

    static function Salarie_Select_byMail($mail)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
        select * 
        from `salarie`
        where mail = :mail');
        $requetePreparee->bindParam('mail', $mail);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête

        $tableauReponse = $requetePreparee->fetchAll(PDO::FETCH_ASSOC);
        if (count($tableauReponse) > 0)
            return $tableauReponse[0];
        else
            return false;
    }

    static function Salarie_Ajouter( string $nom, string $prenom,string $roleEntreprise,string $mail,int   $actif,int $idEntreprise)       
    {
        //Il faut d'abord créer l'utilisateur dans la table utilisateur
        //puis récupérer son idUtilisateur
        Modele_Utilisateur::Utilisateur_Creer($mail, "secret", 4);
        $idSalarie = Modele_Utilisateur::Utilisateur_Select_ParLogin($mail)["idUtilisateur"];

        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(' 
        insert into `salarie` (idSalarie,  nom, prenom, roleEntreprise, mail,  actif, idEntreprise) 
        VALUE (:idSalarie,  :nom, :prenom, :roleEntreprise, :mail, :actif, :idEntreprise)');
        $requetePreparee->bindParam('idSalarie', $idSalarie);
        $requetePreparee->bindParam('nom', $nom);
        $requetePreparee->bindParam('prenom', $prenom);
        $requetePreparee->bindParam('roleEntreprise', $roleEntreprise);
        $requetePreparee->bindParam('mail', $mail);
        $requetePreparee->bindParam('actif', $actif, PDO::PARAM_INT);
        $requetePreparee->bindParam('idEntreprise', $idEntreprise);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête

        return $reponse;
    }


    static function Salarie_MAJ($nom, $prenom, $roleEntreprise, $mail, $idSalarie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare(
            'UPDATE `salarie` 
        SET `nom`= :nom,
            `prenom`= :prenom,
            `roleEntreprise`= :roleEntreprise,
            `mail`= :mail
        WHERE idSalarie = :idSalarie');
        $requetePreparee->bindParam('nom', $nom);
        $requetePreparee->bindParam('prenom', $prenom);
        $requetePreparee->bindParam('roleEntreprise', $roleEntreprise);
        $requetePreparee->bindParam('mail', $mail);
        $requetePreparee->bindParam('idSalarie', $idSalarie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
        return $reponse;


    }

    static function Salarie_Desactiver($idSalarie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
        update salarie
        set actif = 0 
        where idSalarie = :idSalarie');
        $requetePreparee->bindParam('idSalarie', $idSalarie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
    }

    static function Salarie_Activer($idSalarie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
        update salarie
        set actif = 1
        where idSalarie = :idSalarie');
        $requetePreparee->bindParam('idSalarie', $idSalarie);
        $reponse = $requetePreparee->execute(); //$reponse boolean sur l'état de la requête
    }

    static function Salarie_AccepterRGPD($idSalarie)
    {
        $connexionPDO = Singleton_ConnexionPDO::getInstance();
        $requetePreparee = $connexionPDO->prepare('
        update salarie
        set aAccepteRGPD = 1,
            dateAcceptionRGPD = :dateAct
        where idSalarie = :idSalarie');
        $requetePreparee->bindParam('idSalarie', $idSalarie);
        $dateAct = date("Y-m-d H:i:s");
        $requetePreparee->bindParam('dateAct', $dateAct);
        $reponse = $requetePreparee->execute();
    }


}