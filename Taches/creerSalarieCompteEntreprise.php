<?php
include_once "../vendor/autoload.php";

$listeEntreprise = \App\Modele\Modele_Entreprise::Entreprise_Select();

//Requete de purge des utilisateurs de type 3 et 4 :

$requete = "DELETE FROM utilisateur WHERE idCategorie_utilisateur = 3 OR idCategorie_utilisateur = 4";
$bdd = \App\Utilitaire\Singleton_ConnexionPDO::getInstance();
$bdd->exec($requete);


//Création de l'utilisateur de Type 3 par entreprise
foreach ($listeEntreprise as $entreprise){
    $id = \App\Modele\Modele_Utilisateur::Utilisateur_Creer($entreprise["mailContact"], "secret", 3);
    \App\Modele\Modele_Entreprise::Entreprise_Update_IdUtilisateur($entreprise["idEntreprise"], $id);
}

//Création des salariés (1 gérant et 2 vendeurs) et des utilisateurs de type 4 associés
foreach ($listeEntreprise as $entreprise) {
    //Création du gérant
    $mailGerant =strtolower( "gerant@".$entreprise["denomination"]."com");
    $id = \App\Modele\Modele_Utilisateur::Utilisateur_Creer($mailGerant, "1234", 4);
    \App\Modele\Modele_Salarie::Salarie_Ajouter($id, "nomGerant", "prenomGerant", "gerant", $mailGerant, 1, $entreprise["idEntreprise"]);

    //Création des vendeurs
    $mailVendeur1 =strtolower( "vendeur1@".$entreprise["denomination"]."com");
    $id = \App\Modele\Modele_Utilisateur::Utilisateur_Creer($mailVendeur1, "1234", 4);
    \App\Modele\Modele_Salarie::Salarie_Ajouter($id, "nomVendeur1", "prenomVendeur1", "vendeur", $mailVendeur1, 1, $entreprise["idEntreprise"]);

    $mailVendeur2 =strtolower( "vendeur2@".$entreprise["denomination"]."com");
    $id = \App\Modele\Modele_Utilisateur::Utilisateur_Creer($mailVendeur2, "1234", 4);
    \App\Modele\Modele_Salarie::Salarie_Ajouter($id, "nomVendeur2", "prenomVendeur2", "vendeur", $mailVendeur2, 1, $entreprise["idEntreprise"]);

}

