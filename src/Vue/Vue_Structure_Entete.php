<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Structure_Entete  extends Vue_Composant
{
    public function __construct()
    {
    }

    function donneTexte(): string
    {


            return "<html>
        <head>
           <meta charset='utf-8'>
            <!-- importer le fichier de style -->
            <link rel='stylesheet' href='/style.css' media='screen' type='text/css' />
        </head>
        <body>
            <div id='container'>";
    }
}