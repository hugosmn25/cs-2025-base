<?php
include_once "../vendor/autoload.php";

//Code à utiliser en mode console
//Ce code sert à créer un utilisateur du back office, si jamais vous le perdez...



$id = 10;//\App\Modele\Modele_Utilisateur::Utilisateur_Creer("root", "1");

\App\Modele\Modele_Utilisateur::Utilisateur_Modifier_ALL(  "secret");

