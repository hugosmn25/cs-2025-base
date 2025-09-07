<?php
// script php qui génère des jeux d'essai
// à lancer une fois dans un terminal
// php creerJeuxDessai.php

// Chargement autoloader Composer et modèles nécessaires
require_once __DIR__ . '/vendor/autoload.php';

use App\Utilitaire\Singleton_ConnexionPDO;
use App\Modele\Modele_Utilisateur;
use App\Modele\Modele_Entreprise;
use App\Modele\Modele_Salarie;
use App\Modele\Modele_Commande;
use App\Modele\Modele_Catalogue;

function println($msg) { echo $msg . PHP_EOL; }

//try {
    $pdo = Singleton_ConnexionPDO::getInstance();
    println('Connexion BDD OK');

    // Suppression des données dans l'ordre des contraintes (FK)
    println('Purge des données...');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('DELETE FROM commande_avoir_produit');
    $pdo->exec('DELETE FROM historique_etat_commande');
    $pdo->exec('DELETE FROM commande');
    $pdo->exec('DELETE FROM salarie');
    $pdo->exec('DELETE FROM entreprise');
    $pdo->exec('DELETE FROM utilisateur');
    println('Tables vidées: commande_avoir_produit, historique_etat_commande, commande, salarie, entreprise, utilisateur');

    // Corrige la contrainte FK erronée sur commande (si présente)
    try {
        $pdo->exec('ALTER TABLE commande DROP FOREIGN KEY idUtilisateur');
        println('FK idUtilisateur supprimée de commande');
    } catch (Throwable $e) {
        // ignorer si déjà supprimée
    }
    try {
        $pdo->exec('ALTER TABLE commande ADD CONSTRAINT fk_commande_entreprise FOREIGN KEY (idEntreprise) REFERENCES entreprise(idEntreprise)');
        println('FK fk_commande_entreprise ajoutée sur commande(idEntreprise)');
    } catch (Throwable $e) {
        // ignorer si déjà correcte
    }

    // Réinitialisation des auto-incréments sur les tables concernées
    // Attention: ne pas faire sur salarie (idSalarie non AUTO_INCREMENT)
    println('Réinitialisation des clés AUTO_INCREMENT...');
    $pdo->exec('ALTER TABLE utilisateur AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE entreprise AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE commande AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE historique_etat_commande AUTO_INCREMENT = 1');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    // Création des utilisateurs spéciaux
    println("Création de l'utilisateur root (administrateur)...");
    $idRoot = Modele_Utilisateur::Utilisateur_Creer('root@cafe.local', 'root', 1);
    println("root id=$idRoot");

    println("Création de l'utilisateur gestionnaire (catalogue)...");
    $idGest = Modele_Utilisateur::Utilisateur_Creer('gestionnaire@cafe.local', 'gestion', 2);
    println("gestionnaire id=$idGest");

    println("Création de l'utilisateur commercial...");
    $idCom = Modele_Utilisateur::Utilisateur_Creer('commercial@cafe.local', 'commercial', 5);
    println("commercial id=$idCom");

    // Création de 5 entreprises clientes
    $entreprisesData = [
        ['Denomination' => 'Zoombox',   'Ville' => 'Marmande',   'Domaine' => 'zoombox.com'],
        ['Denomination' => 'Edgeblab',  'Ville' => 'Montigny',   'Domaine' => 'edgeblab.com'],
        ['Denomination' => 'Gabcube',   'Ville' => 'Nantes',     'Domaine' => 'gabcube.com'],
        ['Denomination' => 'Jazzy',     'Ville' => 'Rueil',      'Domaine' => 'jazzy.com'],
        ['Denomination' => 'Devbug',    'Ville' => 'Reims',      'Domaine' => 'devbug.com'],
    ];

    $idsEntreprises = [];
    foreach ($entreprisesData as $e) {
        $denom = $e['Denomination'];
        $mail = 'contact@' . $e['Domaine'];
        $siret = strval(rand(100000000, 999999999)) . '0001';
        $idEnt = Modele_Entreprise::Entreprise_Creer(
            $denom,
            '1 rue ' . $denom,
            '',
            strval(rand(10000, 95999)) . ' CEDEX',
            $e['Ville'],
            'France',
            $mail,
            $siret
        );
        $idsEntreprises[] = $idEnt;
        println("Entreprise créée: $denom (id=$idEnt)");
    }

    // Création de 2 salariés par entreprise
    $idsSalariesParEntreprise = [];
    foreach ($idsEntreprises as $idx => $idEnt) {
        $denom = $entreprisesData[$idx]['Denomination'];
        $base = strtolower(str_replace(' ', '', $denom));
        $sals = [];
        $s1 = Modele_Salarie::Salarie_Ajouter('Gerant', 'Auto', 'Gérant', 'gerant@' . $base . '.local', 1, $idEnt);
        $sals[] = App\Modele\Modele_Utilisateur::Utilisateur_Select_ParLogin('gerant@' . $base . '.local')['idUtilisateur'];
        $s2 = Modele_Salarie::Salarie_Ajouter('Vendeur', 'Un', 'Vendeur', 'vendeur1@' . $base . '.local', 1, $idEnt);
        $sals[] = App\Modele\Modele_Utilisateur::Utilisateur_Select_ParLogin('vendeur1@' . $base . '.local')['idUtilisateur'];
        $idsSalariesParEntreprise[$idEnt] = $sals;
        println("2 salariés créés pour $denom (idEntreprise=$idEnt)");
    }

    // Liste produits pour composer les commandes
    $produits = Modele_Catalogue::Produit_Select();
    if (!$produits || count($produits) === 0) {
        println('Aucun produit en base, arrêt.');
        exit(1);
    }

    // Création de 10 à 15 commandes par salarié avec chronologie respectée
    foreach ($idsEntreprises as $idEnt) {
        foreach ($idsSalariesParEntreprise[$idEnt] as $idSal) {
            $nbCmd = rand(10, 15);
            for ($c = 0; $c < $nbCmd; $c++) {
                // Ajouter 1 à 5 articles aléatoires dans le caddie
                $nbArticles = rand(1, 5);
                for ($i = 0; $i < $nbArticles; $i++) {
                    $p = $produits[array_rand($produits)];
                    Modele_Commande::Panier_Ajouter_Produit_ParIdProduit($idEnt, intval($p['idProduit']));
                }
                // Récupère le panier et le valide -> passe à l'état 2 + historique
                $panier = Modele_Commande::Caddie_Select_ParIdEntreprise($idEnt);
                if ($panier === false) {
                    // sécurité: si aucun panier, on en crée un en ajoutant un produit random
                    $p = $produits[array_rand($produits)];
                    Modele_Commande::Panier_Ajouter_Produit_ParIdProduit($idEnt, intval($p['idProduit']));
                    $panier = Modele_Commande::Caddie_Select_ParIdEntreprise($idEnt);
                }
                $idCommande = intval($panier['id']);
                Modele_Commande::Commande_Valider_Caddie($idCommande, $idSal);

                // Choisir un état final aléatoire (entre 2 et 7)
                $etatFinal = rand(2, 7);
                // Avancer progressivement de 3 jusqu'à l'état final, avec historique
                for ($etat = 3; $etat <= $etatFinal; $etat++) {
                    // alterner un utilisateur système (gestionnaire/commercial)
                    $idUserSystem = (rand(0, 1) === 0) ? $idGest : $idCom;
                    Modele_Commande::HistoriqueEtatCommande_Inserer($idCommande, $etat, '', -1, $idUserSystem);
                }
            }
            println("$nbCmd commandes créées pour salarie id=$idSal (entreprise id=$idEnt)");
        }
    }

    println('Jeux d\'essai créés avec succès.');
/*} catch (Throwable $e) {
    fwrite(STDERR, 'Erreur: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}*/
